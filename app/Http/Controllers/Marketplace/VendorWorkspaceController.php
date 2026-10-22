<?php

namespace App\Http\Controllers\Marketplace;

use App\Exceptions\InventoryException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductLocation;
use App\Models\ProductRequest;
use App\Models\Shopcategory;
use App\Models\ShopProduct;
use App\Services\InventoryService;
use App\Services\OrderService;
use App\Services\ProductWorkflowService;
use App\Services\RequestWorkflowService;
use App\Support\ProductPayload;
use App\Support\PublicAsset;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VendorWorkspaceController extends Controller
{
    public function __construct(
        private ProductWorkflowService $products,
        private RequestWorkflowService $requests,
        private OrderService $orders,
        private InventoryService $inventory,
    ) {}

    public function dashboard()
    {
        $vendorId = auth()->user()->vendor_id;

        return view('vendor.dashboard', [
            'products' => ShopProduct::query()->ownedByVendor($vendorId)->count(),
            'pending' => ShopProduct::query()->ownedByVendor($vendorId)->where('approval_status', 'pending_approval')->count(),
            'requests' => ProductRequest::query()->ownedByVendor($vendorId)->where('status', 'awaiting_vendor_response')->count(),
            'orders' => OrderItem::query()->ownedByVendor($vendorId)->distinct('order_id')->count('order_id'),
        ]);
    }

    public function productsIndex()
    {
        abort_unless(auth()->user()->hasPermission('products.view'), 403);
        $products = ShopProduct::query()->ownedByVendor(auth()->user()->vendor_id)->latest()->paginate(15);

        return view('vendor.products.index', compact('products'));
    }

    public function createProduct()
    {
        abort_unless(auth()->user()->hasPermission('products.submit'), 403);

        return view('vendor.products.form', [
            'product' => new ShopProduct,
            'parentCategories' => Shopcategory::with('subcategories')->whereNull('parent_id')->orderBy('name')->get(),
        ]);
    }

    public function storeProduct(Request $request)
    {
        abort_unless($request->user()->hasPermission('products.submit'), 403);
        $validated = $this->payload($request, null);

        $product = $this->products->createVendor($request->user(), $validated, $request->boolean('submit_for_review'));

        return redirect()->route('vendor.products.index')->with('success', $product->approval_status === 'pending_approval'
            ? 'Product submitted for approval. It is not public yet.'
            : 'Draft saved. Submit it when you are ready for review.');
    }

    public function editProduct(string $slug)
    {
        $product = $this->findProduct($slug);

        return view('vendor.products.form', [
            'product' => $product,
            'parentCategories' => Shopcategory::with('subcategories')->whereNull('parent_id')->orderBy('name')->get(),
        ]);
    }

    public function updateProduct(Request $request, string $slug)
    {
        $product = $this->findProduct($slug);
        $validated = $this->payload($request, $product);
        $this->products->updateVendor($product, $request->user(), $validated, $request->boolean('submit_for_review'));

        return redirect()->route('vendor.products.index')->with('success', 'Product saved.');
    }

    public function requestsIndex()
    {
        abort_unless(auth()->user()->hasPermission('requests.view_assigned'), 403);
        $requests = ProductRequest::query()->ownedByVendor(auth()->user()->vendor_id)->latest()->paginate(15);

        return view('vendor.requests.index', compact('requests'));
    }

    public function showRequest(string $reference)
    {
        $record = ProductRequest::query()->ownedByVendor(auth()->user()->vendor_id)->where('reference', $reference)->firstOrFail();
        $record->load('product', 'histories');

        return view('vendor.requests.show', compact('record'));
    }

    public function respondRequest(Request $request, string $reference)
    {
        $record = ProductRequest::query()->ownedByVendor(auth()->user()->vendor_id)->where('reference', $reference)->firstOrFail();
        $data = $request->validate([
            'decision' => ['required', 'in:accept,decline,clarification'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $this->requests->vendorDecision($record, $request->user(), $data['decision'], $data['note'] ?? null);

        return back()->with('success', 'Response recorded.');
    }

    public function ordersIndex()
    {
        abort_unless(auth()->user()->hasPermission('orders.view_assigned'), 403);
        $items = OrderItem::query()->ownedByVendor(auth()->user()->vendor_id)->with('order')->latest()->paginate(15);

        return view('vendor.orders.index', compact('items'));
    }

    public function showOrder(string $reference)
    {
        $order = Order::query()->where('reference', $reference)->firstOrFail();
        $items = OrderItem::query()->ownedByVendor(auth()->user()->vendor_id)->where('order_id', $order->id)->with('location')->get();
        abort_if($items->isEmpty(), 404);

        return view('vendor.orders.show', compact('order', 'items'));
    }

    public function respondOrder(Request $request, OrderItem $item)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:accept,decline,clarification'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $this->orders->vendorDecision($item, $request->user(), $data['decision'], $data['note'] ?? null);

        return back()->with('success', 'Response recorded.');
    }

    public function progressOrder(Request $request, OrderItem $item)
    {
        $data = $request->validate([
            'fulfillment_status' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->orders->vendorProgress($item, $request->user(), $data['fulfillment_status'], $data['note'] ?? null);
        } catch (InventoryException $exception) {
            throw ValidationException::withMessages(['fulfillment_status' => $exception->getMessage()]);
        }

        return back()->with('success', 'Fulfillment updated.');
    }

    public function inventory()
    {
        abort_unless(auth()->user()->hasPermission('inventory.view'), 403);
        $products = ShopProduct::query()->ownedByVendor(auth()->user()->vendor_id)->with('locations')->where('tracks_inventory', true)->get();

        return view('vendor.inventory', compact('products'));
    }

    public function adjust(Request $request, string $slug)
    {
        abort_unless($request->user()->hasPermission('inventory.manage'), 403);
        $product = $this->findProduct($slug);
        $data = $request->validate([
            'delta' => ['required', 'integer', 'not_in:0'],
            'product_location_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $location = null;
        if (! empty($data['product_location_id'])) {
            $location = ProductLocation::query()->where('shop_product_id', $product->id)->findOrFail($data['product_location_id']);
        }

        try {
            $this->inventory->adjust($product, (int) $data['delta'], $location, $request->user(), $data['note'] ?? null);
        } catch (InventoryException $exception) {
            throw ValidationException::withMessages(['delta' => $exception->getMessage()]);
        }

        return back()->with('success', 'Inventory updated.');
    }

    public function profile()
    {
        return view('vendor.profile', ['vendor' => auth()->user()->vendor]);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate(['phone' => ['nullable', 'string', 'max:50']]);
        $request->user()->update(['phone' => $data['phone'] ?? null]);

        return back()->with('success', 'Phone number saved. Business details are maintained by TRESCA Hub.');
    }

    private function findProduct(string $slug): ShopProduct
    {
        abort_unless(auth()->user()->hasPermission('products.edit') || auth()->user()->hasPermission('products.view'), 403);

        return ShopProduct::query()->ownedByVendor(auth()->user()->vendor_id)->where('slug', $slug)->firstOrFail();
    }

    private function payload(Request $request, ?ShopProduct $product): array
    {
        $validated = ProductPayload::normalize($request->validate(ProductPayload::rules($product?->id)));
        unset($validated['vendor_id'], $validated['acquisition_cost'], $validated['internal_notes'], $validated['approve_now'], $validated['publish_now']);

        $validated['thumbnail'] = $product?->thumbnail;
        $validated['images'] = $product?->images ?? [];

        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = PublicAsset::store($request->file('thumbnail'), 'shop-products/thumbnails');
        }

        if ($request->hasFile('images')) {
            $validated['images'] = [];
            foreach ($request->file('images') as $image) {
                $validated['images'][] = PublicAsset::store($image, 'shop-products/images');
            }
        }

        return $validated;
    }
}

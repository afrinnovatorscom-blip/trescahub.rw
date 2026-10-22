<?php

namespace App\Http\Controllers;

use App\Models\Shopcategory;
use App\Models\ShopProduct;
use App\Services\ProductWorkflowService;
use App\Support\ProductPayload;
use App\Support\PublicAsset;
use App\Support\PublicProduct;
use Illuminate\Http\Request;

class ShopProductController extends Controller
{
    public function __construct(private ProductWorkflowService $workflow) {}

    public function publicIndex(Request $request)
    {
        $popularProducts = ShopProduct::query()->publicCatalog()
            ->with(['category', 'subcategory'])
            ->where('is_popular', true)
            ->latest()
            ->get();

        $products = ShopProduct::query()->publicCatalog()
            ->with(['category', 'subcategory'])
            ->when($request->category, function ($query) use ($request) {
                $query->whereHas('category', fn ($category) => $category->where('slug', $request->category));
            })
            ->when($request->subcategory, function ($query) use ($request) {
                $query->whereHas('subcategory', fn ($category) => $category->where('slug', $request->subcategory));
            })
            ->when($request->status, function ($query) use ($request) {
                if ($request->status === 'in_stock') {
                    $query->where('status', 'in_stock');
                } elseif ($request->status === 'out_of_stock') {
                    $query->where('status', 'out_of_stock');
                }
            })
            ->when($request->search, function ($query) use ($request) {
                $term = '%'.$request->search.'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('product_name', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->latest()
            ->get();

        $parentCategories = Shopcategory::with('subcategories')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('front.pages.shop', compact('products', 'popularProducts', 'parentCategories'));
    }

    public function publicShow(ShopProduct $shopProduct)
    {
        abort_unless($shopProduct->isPubliclyVisible(), 404);

        $shopProduct->load(['category', 'subcategory']);

        $related = ShopProduct::query()->publicCatalog()
            ->with(['category', 'subcategory'])
            ->where('shop_category_id', $shopProduct->shop_category_id)
            ->where('id', '!=', $shopProduct->id)
            ->limit(4)
            ->get();

        return view('front.pages.shopproduct', compact('shopProduct', 'related'));
    }

    public function publicApi(Request $request)
    {
        $products = ShopProduct::query()->publicCatalog()
            ->with('category')
            ->latest()
            ->paginate(24)
            ->through(fn (ShopProduct $product) => PublicProduct::make($product));

        return response()->json($products);
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', ShopProduct::class);

        $products = ShopProduct::query()
            ->with(['category', 'subcategory', 'vendor'])
            ->when($request->q, fn ($query) => $query->where('product_name', 'like', '%'.$request->q.'%'))
            ->when($request->approval_status, fn ($query) => $query->where('approval_status', $request->approval_status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('shop-products.index', compact('products'));
    }

    public function create()
    {
        $this->authorize('create', ShopProduct::class);

        $parentCategories = $this->categories();
        $vendors = $this->vendorOptions();

        return view('shop-products.create', compact('parentCategories', 'vendors'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', ShopProduct::class);

        $validated = ProductPayload::normalize($request->validate(ProductPayload::rules()));
        $validated = $this->withUploads($request, $validated, null);
        $user = $request->user();

        $product = $this->workflow->createStaff(
            $user,
            $validated,
            $request->boolean('approve_now') && $user->hasPermission('products.approve'),
            $request->boolean('publish_now') && $user->hasPermission('products.publish') && $request->boolean('approve_now'),
        );

        $message = $product->isPubliclyListed()
            ? 'Product approved and published.'
            : 'Product saved. It stays off the public shop until it is approved and published.';

        return redirect()->route('admin.shop-products.index')->with('success', $message);
    }

    public function show(ShopProduct $shopProduct)
    {
        $this->authorize('view', $shopProduct);
        $shopProduct->load(['category', 'subcategory', 'vendor', 'locations', 'approvalHistories.actor', 'creator', 'submitter', 'approver']);

        return view('marketplace.products.show', [
            'product' => $shopProduct,
            'vendors' => $this->vendorOptions(),
        ]);
    }

    public function edit(ShopProduct $shopProduct)
    {
        $this->authorize('update', $shopProduct);
        $shopProduct->load(['category', 'subcategory']);
        $parentCategories = $this->categories();
        $vendors = $this->vendorOptions();

        return view('shop-products.edit', compact('shopProduct', 'parentCategories', 'vendors'));
    }

    public function update(Request $request, ShopProduct $shopProduct)
    {
        $this->authorize('update', $shopProduct);

        $validated = ProductPayload::normalize($request->validate(ProductPayload::rules($shopProduct->id)));
        $validated = $this->withUploads($request, $validated, $shopProduct);
        $user = $request->user();

        $this->workflow->updateStaff($shopProduct, $user, $validated);

        if ($request->boolean('publish_now') && $user->hasPermission('products.publish')) {
            $this->workflow->publish($shopProduct->refresh(), $user);
        }

        return redirect()->route('admin.shop-products.index')->with('success', 'Product updated.');
    }

    public function destroy(ShopProduct $shopProduct)
    {
        $this->authorize('delete', $shopProduct);

        PublicAsset::delete($shopProduct->thumbnail);
        foreach ($shopProduct->images ?? [] as $image) {
            PublicAsset::delete($image);
        }

        $name = $shopProduct->product_name;
        $shopProduct->delete();

        return redirect()->route('admin.shop-products.index')->with('success', "Product '{$name}' deleted.");
    }

    private function categories()
    {
        return Shopcategory::with('subcategories')->whereNull('parent_id')->orderBy('name')->get();
    }

    private function vendorOptions()
    {
        if (! auth()->user()->hasPermission('vendors.view') && ! auth()->user()->hasPermission('products.assign_vendor')) {
            return collect();
        }

        return \App\Models\Vendor::query()->orderBy('business_name')->get(['id', 'business_name', 'status']);
    }

    private function withUploads(Request $request, array $validated, ?ShopProduct $product): array
    {
        $validated['thumbnail'] = $product?->thumbnail;
        $validated['images'] = $product?->images ?? [];

        if ($request->hasFile('thumbnail')) {
            PublicAsset::delete($product?->thumbnail);
            $validated['thumbnail'] = PublicAsset::store($request->file('thumbnail'), 'shop-products/thumbnails');
        }

        if ($request->hasFile('images')) {
            foreach ($product?->images ?? [] as $old) {
                PublicAsset::delete($old);
            }
            $validated['images'] = [];
            foreach ($request->file('images') as $image) {
                $validated['images'][] = PublicAsset::store($image, 'shop-products/images');
            }
        }

        return $validated;
    }
}

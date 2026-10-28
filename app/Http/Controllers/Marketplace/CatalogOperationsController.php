<?php

namespace App\Http\Controllers\Marketplace;

use App\Exceptions\InventoryException;
use App\Http\Controllers\Controller;
use App\Models\InventoryAdjustmentRequest;
use App\Models\InventoryMovement;
use App\Models\ProductLocation;
use App\Models\Shopcategory;
use App\Models\ShopProduct;
use App\Models\Vendor;
use App\Services\AuditLogger;
use App\Services\InventoryAdjustmentRequestService;
use App\Services\InventoryService;
use App\Services\ProductWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CatalogOperationsController extends Controller
{
    public function __construct(
        private ProductWorkflowService $workflow,
        private InventoryService $inventory,
        private InventoryAdjustmentRequestService $adjustmentRequests,
    ) {}

    public function approvals(Request $request)
    {
        abort_unless($request->user()->hasPermission('products.approve'), 403);

        $products = ShopProduct::query()
            ->with(['category', 'vendor', 'submitter'])
            ->where(function ($query) {
                $query->where('approval_status', 'pending_approval')
                    ->orWhere('revision_status', 'pending_approval');
            })
            ->latest('submitted_at')
            ->latest()
            ->paginate(20);

        return view('marketplace.products.approvals', compact('products'));
    }

    public function approve(Request $request, ShopProduct $shopProduct)
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);
        $this->workflow->approve($shopProduct, $request->user(), $data['note'] ?? null);

        return back()->with('success', 'Product approved. Publish it when it should appear on the shop.');
    }

    public function reject(Request $request, ShopProduct $shopProduct)
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);
        $this->workflow->reject($shopProduct, $request->user(), $data['note']);

        return back()->with('success', 'Product rejected.');
    }

    public function requestChanges(Request $request, ShopProduct $shopProduct)
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);
        $this->workflow->requestChanges($shopProduct, $request->user(), $data['note']);

        return back()->with('success', 'Changes requested.');
    }

    public function publish(Request $request, ShopProduct $shopProduct)
    {
        $this->workflow->publish($shopProduct, $request->user());

        return back()->with('success', 'Product published.');
    }

    public function unpublish(Request $request, ShopProduct $shopProduct)
    {
        $this->workflow->unpublish($shopProduct, $request->user());

        return back()->with('success', 'Product unpublished.');
    }

    public function archive(Request $request, ShopProduct $shopProduct)
    {
        $this->workflow->archive($shopProduct, $request->user());

        return back()->with('success', 'Product archived.');
    }

    public function assignVendor(Request $request, ShopProduct $shopProduct)
    {
        abort_unless($request->user()->hasPermission('products.assign_vendor'), 403);

        $data = $request->validate(['vendor_id' => ['nullable', 'exists:vendors,id']]);
        $before = $shopProduct->vendor_id;
        $shopProduct->vendor_id = $data['vendor_id'] ?? null;
        $shopProduct->save();
        AuditLogger::log($request->user(), 'product.vendor_assigned', $shopProduct, ['from' => $before, 'to' => $shopProduct->vendor_id]);

        return back()->with('success', 'Vendor assignment saved.');
    }

    public function storeLocation(Request $request, ShopProduct $shopProduct)
    {
        abort_unless($request->user()->hasPermission('locations.manage'), 403);

        $data = $request->validate([
            'warehouse_name' => ['required', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'shelf_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'quantity' => ['nullable', 'integer', 'min:0'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $quantity = (int) ($data['quantity'] ?? 0);
        unset($data['quantity']);

        if ($request->boolean('is_primary')) {
            $shopProduct->locations()->update(['is_primary' => false]);
        }

        $location = $shopProduct->locations()->create($data + [
            'is_primary' => $request->boolean('is_primary'),
            'quantity' => 0,
            'reserved_quantity' => 0,
        ]);

        if ($quantity > 0) {
            try {
                $this->inventory->adjust($shopProduct, $quantity, $location, $request->user(), 'Opening quantity');
            } catch (InventoryException $exception) {
                return back()->withErrors(['quantity' => $exception->getMessage()]);
            }
        }

        AuditLogger::log($request->user(), 'location.created', $location);

        return back()->with('success', 'Location saved.');
    }

    public function destroyLocation(Request $request, ProductLocation $location)
    {
        abort_unless($request->user()->hasPermission('locations.manage'), 403);

        if ($location->reserved_quantity > 0 || $location->quantity > 0) {
            return back()->withErrors(['location' => 'Move the stock out of this location before removing it.']);
        }

        $location->delete();

        return back()->with('success', 'Location removed.');
    }

    public function inventory(Request $request)
    {
        abort_unless($request->user()->hasPermission('admin.access') && $request->user()->hasPermission('inventory.view'), 403);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'shop_category_id' => ['nullable', 'integer', 'exists:shop_categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:shop_categories,id'],
            'vendor_id' => ['nullable', 'integer'],
            'stock_status' => ['nullable', 'in:in_stock,low_stock,out_of_stock,finished'],
            'approval_status' => ['nullable', 'in:draft,pending_approval,approved,rejected,changes_requested'],
            'sort' => ['nullable', 'in:product_name,sku,available,stock_quantity,low_stock_threshold'],
            'direction' => ['nullable', 'in:asc,desc'],
            'request_status' => ['nullable', 'in:pending,approved,rejected'],
            'movement_product' => ['nullable', 'integer', 'exists:shop_products,id'],
            'movement_vendor' => ['nullable', 'integer'],
            'movement_type' => ['nullable', 'in:addition,removal,adjustment,reservation,release,sale,return'],
            'movement_from' => ['nullable', 'date'],
            'movement_to' => ['nullable', 'date', 'after_or_equal:movement_from'],
        ]);
        if (isset($filters['vendor_id'])) {
            abort_unless($request->user()->hasPermission('vendors.view'), 403);
        }

        $availableExpression = '(stock_quantity - reserved_quantity)';

        $baseProducts = ShopProduct::query()->where('tracks_inventory', true)
            ->with(['category', 'subcategory', 'vendor', 'locations'])
            ->withCount(['inventoryAdjustmentRequests as pending_adjustment_requests_count' => fn ($query) => $query->where('status', 'pending')])
            ->when($filters['q'] ?? null, function ($query, $term) {
                $query->where(function ($search) use ($term) {
                    $search->where('product_name', 'like', '%'.$term.'%')
                        ->orWhere('sku', 'like', '%'.$term.'%');
                });
            })
            ->when($filters['shop_category_id'] ?? null, fn ($query, $id) => $query->where('shop_category_id', $id))
            ->when($filters['subcategory_id'] ?? null, fn ($query, $id) => $query->where('subcategory_id', $id))
            ->when($filters['approval_status'] ?? null, fn ($query, $status) => $query->where('approval_status', $status));
        if (array_key_exists('vendor_id', $filters)) {
            (int) $filters['vendor_id'] === 0
                ? $baseProducts->whereNull('vendor_id')
                : $baseProducts->where('vendor_id', $filters['vendor_id']);
        }

        $stockStatus = $filters['stock_status'] ?? null;
        if ($stockStatus === 'in_stock') {
            $baseProducts->where('status', 'in_stock')->whereRaw($availableExpression.' > low_stock_threshold')->where('is_finished', false);
        } elseif ($stockStatus === 'low_stock') {
            $baseProducts->where('status', 'in_stock')->whereRaw($availableExpression.' > 0 AND '.$availableExpression.' <= low_stock_threshold')->where('is_finished', false);
        } elseif ($stockStatus === 'out_of_stock') {
            $baseProducts->where(function ($query) use ($availableExpression) {
                $query->where('status', 'out_of_stock')->orWhereRaw($availableExpression.' <= 0');
            })->where('is_finished', false);
        } elseif ($stockStatus === 'finished') {
            $baseProducts->where('is_finished', true);
        }

        $sort = $filters['sort'] ?? 'product_name';
        $direction = $filters['direction'] ?? 'asc';
        if ($sort === 'available') {
            $baseProducts->orderByRaw($availableExpression.' '.$direction);
        } else {
            $baseProducts->orderBy($sort, $direction);
        }

        $products = $baseProducts->paginate(20)->withQueryString();
        $categories = Shopcategory::query()->whereNull('parent_id')->orderBy('name')->get();
        $vendors = Vendor::query()->orderBy('business_name')->get(['id', 'business_name']);
        $productOptions = ShopProduct::query()->where('tracks_inventory', true)
            ->when($filters['vendor_id'] ?? null, fn ($query, $vendorId) => $query->where('vendor_id', $vendorId))
            ->orderBy('product_name')
            ->get(['id', 'product_name', 'sku']);

        $requests = InventoryAdjustmentRequest::query()
            ->with(['product', 'vendor', 'requester', 'reviewer'])
            ->when($filters['request_status'] ?? 'pending', fn ($query, $status) => $query->where('status', $status))
            ->when(array_key_exists('vendor_id', $filters), fn ($query) => $query->where('vendor_id', $filters['vendor_id']))
            ->latest()
            ->paginate(10, ['*'], 'request_page')
            ->withQueryString();

        $movementQuery = InventoryMovement::query()->with(['product.vendor', 'actor'])
            ->when($filters['movement_product'] ?? null, fn ($query, $id) => $query->where('shop_product_id', $id))
            ->when($filters['movement_type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['movement_from'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['movement_to'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date));
        if (array_key_exists('movement_vendor', $filters)) {
            $movementQuery->whereHas('product', fn ($product) => (int) $filters['movement_vendor'] === 0
                ? $product->whereNull('vendor_id')
                : $product->where('vendor_id', $filters['movement_vendor']));
        } elseif (array_key_exists('vendor_id', $filters)) {
            $movementQuery->whereHas('product', fn ($product) => $product->where('vendor_id', $filters['vendor_id']));
        }
        $movements = $movementQuery->latest()->paginate(20, ['*'], 'movement_page')->withQueryString();

        $unitTotals = ShopProduct::query()->where('tracks_inventory', true)
            ->when($filters['vendor_id'] ?? null, fn ($query, $vendorId) => $query->where('vendor_id', $vendorId))
            ->selectRaw("COALESCE(NULLIF(stock_unit, ''), 'Unspecified') as unit_name, SUM(stock_quantity - reserved_quantity) as available_quantity")
            ->groupBy('shop_products.stock_unit')
            ->orderBy('unit_name')
            ->get();

        return view('marketplace.inventory.index', compact('products', 'movements', 'requests', 'categories', 'vendors', 'productOptions', 'filters', 'unitTotals'));
    }

    public function inventoryProductsForCategory(Request $request, int $categoryId)
    {
        abort_unless($request->user()->hasPermission('admin.access') && $request->user()->hasPermission('inventory.manage'), 403);

        $category = Shopcategory::query()->whereNull('parent_id')->findOrFail($categoryId);
        $products = ShopProduct::query()
            ->where('shop_category_id', $category->id)
            ->with(['locations' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('id')])
            ->orderBy('product_name')
            ->get(['id', 'product_name', 'sku', 'shop_category_id', 'tracks_inventory', 'stock_quantity', 'reserved_quantity', 'stock_unit']);

        return response()->json([
            'products' => $products->map(fn (ShopProduct $product) => [
                'id' => $product->id,
                'name' => $product->product_name,
                'sku' => $product->sku,
                'current_stock' => (int) $product->stock_quantity,
                'available_stock' => $product->availableQuantity(),
                'reserved_stock' => (int) $product->reserved_quantity,
                'unit' => $product->stock_unit,
                'tracks_inventory' => (bool) $product->tracks_inventory,
                'locations' => $product->locations->map(fn (ProductLocation $location) => [
                    'id' => $location->id,
                    'available_stock' => $location->availableQuantity(),
                ])->values(),
            ])->values(),
        ]);
    }

    public function approveInventoryRequest(Request $request, InventoryAdjustmentRequest $inventoryAdjustmentRequest)
    {
        $this->adjustmentRequests->approve($inventoryAdjustmentRequest, $request->user());

        return back()->with('success', 'Inventory request approved and stock movement recorded.');
    }

    public function rejectInventoryRequest(Request $request, InventoryAdjustmentRequest $inventoryAdjustmentRequest)
    {
        $data = $request->validate(['review_note' => ['required', 'string', 'max:2000']]);
        $this->adjustmentRequests->reject($inventoryAdjustmentRequest, $request->user(), $data['review_note']);

        return back()->with('success', 'Inventory request rejected.');
    }

    public function adjust(Request $request, ShopProduct $shopProduct)
    {
        abort_unless($request->user()->hasPermission('admin.access') && $request->user()->hasPermission('inventory.manage'), 403);

        $data = $request->validate([
            'delta' => ['required', 'integer', 'not_in:0'],
            'product_location_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $location = null;
        if (! empty($data['product_location_id'])) {
            $location = ProductLocation::query()
                ->where('shop_product_id', $shopProduct->id)
                ->findOrFail($data['product_location_id']);
        }

        try {
            $this->inventory->adjust($shopProduct, (int) $data['delta'], $location, $request->user(), $data['note'] ?? null);
        } catch (InventoryException $exception) {
            throw ValidationException::withMessages(['delta' => $exception->getMessage()]);
        }

        return back()->with('success', 'Stock updated.');
    }

    public function addInventory(Request $request)
    {
        abort_unless($request->user()->hasPermission('admin.access') && $request->user()->hasPermission('inventory.manage'), 403);

        $data = $request->validate([
            'inventory_form' => ['required', 'in:add'],
            'shop_category_id' => ['required', 'integer', Rule::exists('shop_categories', 'id')->whereNull('parent_id')],
            'product_id' => ['required', 'integer', 'exists:shop_products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'submission_token' => ['required', 'uuid'],
        ]);

        $product = ShopProduct::query()
            ->where('shop_category_id', $data['shop_category_id'])
            ->with(['locations' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('id')])
            ->find($data['product_id']);
        if (! $product) {
            throw ValidationException::withMessages(['product_id' => 'Choose a product from the selected category.']);
        }
        $location = $product->locations->first();
        $note = trim($data['reason'].(filled($data['notes'] ?? null) ? "\n".$data['notes'] : ''));

        try {
            $added = $this->inventory->adjust(
                $product,
                (int) $data['quantity'],
                $location,
                $request->user(),
                $note,
                submissionToken: $data['submission_token'],
                movementType: 'addition',
            );
        } catch (InventoryException $exception) {
            throw ValidationException::withMessages(['quantity' => $exception->getMessage()]);
        }

        return back()->with('success', $added
            ? 'Inventory saved and stock movement recorded.'
            : 'This inventory submission was already saved; stock was not changed again.');
    }

    public function removeInventory(Request $request)
    {
        abort_unless($request->user()->hasPermission('admin.access') && $request->user()->hasPermission('inventory.manage'), 403);

        $data = $request->validate([
            'inventory_form' => ['required', 'in:remove'],
            'shop_category_id' => ['required', 'integer', Rule::exists('shop_categories', 'id')->whereNull('parent_id')],
            'product_id' => ['required', 'integer', 'exists:shop_products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'submission_token' => ['required', 'uuid'],
        ]);

        $product = ShopProduct::query()
            ->where('shop_category_id', $data['shop_category_id'])
            ->where('tracks_inventory', true)
            ->with(['locations' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('id')])
            ->find($data['product_id']);
        if (! $product) {
            throw ValidationException::withMessages(['product_id' => 'Choose an inventory-tracked product from the selected category.']);
        }

        $quantity = (int) $data['quantity'];
        if ($quantity > $product->availableQuantity()) {
            throw ValidationException::withMessages(['quantity' => 'Quantity to remove cannot exceed available stock.']);
        }

        $location = null;
        if ($product->locations->isNotEmpty()) {
            $location = $product->locations->first(fn (ProductLocation $candidate) => $candidate->availableQuantity() >= $quantity);
            if (! $location) {
                throw ValidationException::withMessages(['quantity' => 'No single stock location has enough unreserved stock for this removal.']);
            }
        }

        $note = trim($data['reason'].(filled($data['notes'] ?? null) ? "\n".$data['notes'] : ''));
        try {
            $removed = $this->inventory->adjust(
                $product,
                -$quantity,
                $location,
                $request->user(),
                $note,
                submissionToken: $data['submission_token'],
                movementType: 'removal',
            );
        } catch (InventoryException $exception) {
            throw ValidationException::withMessages(['quantity' => $exception->getMessage()]);
        }

        return back()->with('success', $removed
            ? 'Stock removed and inventory movement recorded.'
            : 'This inventory submission was already processed; stock was not changed again.');
    }

    public function updateInventory(Request $request, ShopProduct $shopProduct)
    {
        abort_unless($request->user()->hasPermission('admin.access') && $request->user()->hasPermission('inventory.manage'), 403);

        $data = $request->validate([
            'shop_category_id' => ['required', 'integer', Rule::exists('shop_categories', 'id')->whereNull('parent_id')],
            'inventory_notes' => ['nullable', 'string', 'max:5000'],
            'edit_product_id' => ['required', 'integer', 'in:'.$shopProduct->id],
        ]);

        abort_unless($shopProduct->tracks_inventory, 404);

        DB::transaction(function () use ($shopProduct, $data, $request) {
            $product = ShopProduct::query()->whereKey($shopProduct->id)->lockForUpdate()->firstOrFail();
            $previousCategory = $product->shop_category_id;
            $product->shop_category_id = (int) $data['shop_category_id'];
            $product->inventory_notes = $data['inventory_notes'] ?? null;

            if ($product->subcategory_id && ! $product->subcategory()->where('parent_id', $product->shop_category_id)->exists()) {
                $product->subcategory_id = null;
            }

            $product->save();
            AuditLogger::log($request->user(), 'inventory.details_updated', $product, [
                'category_from' => $previousCategory,
                'category_to' => $product->shop_category_id,
            ]);
        });

        return back()->with('success', 'Inventory details saved. Stock was not changed.');
    }
}

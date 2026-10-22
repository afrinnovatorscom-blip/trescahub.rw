<?php

namespace App\Http\Controllers\Marketplace;

use App\Exceptions\InventoryException;
use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\ProductLocation;
use App\Models\ShopProduct;
use App\Models\Vendor;
use App\Services\AuditLogger;
use App\Services\InventoryService;
use App\Services\ProductWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CatalogOperationsController extends Controller
{
    public function __construct(
        private ProductWorkflowService $workflow,
        private InventoryService $inventory,
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
        abort_unless($request->user()->hasPermission('inventory.view'), 403);

        $products = ShopProduct::query()
            ->with('locations')
            ->where('tracks_inventory', true)
            ->when($request->boolean('low'), function ($query) {
                $query->whereRaw('(stock_quantity - reserved_quantity) <= low_stock_threshold');
            })
            ->orderBy('product_name')
            ->paginate(20);

        $movements = InventoryMovement::query()->with(['product', 'actor'])->latest()->limit(15)->get();

        return view('marketplace.inventory.index', compact('products', 'movements'));
    }

    public function adjust(Request $request, ShopProduct $shopProduct)
    {
        abort_unless($request->user()->hasPermission('inventory.manage'), 403);

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
}

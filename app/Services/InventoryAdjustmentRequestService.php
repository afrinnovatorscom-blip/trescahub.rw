<?php

namespace App\Services;

use App\Exceptions\InventoryException;
use App\Models\InventoryAdjustmentRequest;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryAdjustmentRequestService
{
    public function __construct(
        private InventoryService $inventory,
        private MarketplaceNotifier $notifier,
    ) {}

    public function submit(User $vendorUser, ShopProduct $product, string $type, int $quantity, string $reason): InventoryAdjustmentRequest
    {
        abort_unless(
            $vendorUser->hasPermission('inventory.manage')
                && $vendorUser->vendor_id
                && (int) $vendorUser->vendor_id === (int) $product->vendor_id,
            403
        );

        if (! in_array($type, ['addition', 'reduction', 'correction'], true) || $quantity < 1) {
            throw ValidationException::withMessages(['quantity' => 'Choose a valid stock change and quantity.']);
        }

        $request = DB::transaction(function () use ($vendorUser, $product, $type, $quantity, $reason) {
            $locked = ShopProduct::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            if (! $locked->tracks_inventory) {
                throw ValidationException::withMessages(['product' => 'Inventory tracking is not enabled for this product.']);
            }

            $delta = match ($type) {
                'addition' => $quantity,
                'reduction' => -$quantity,
                'correction' => $quantity - (int) $locked->stock_quantity,
            };

            if ($type === 'reduction' && ($quantity > (int) $locked->stock_quantity - (int) $locked->reserved_quantity)) {
                throw ValidationException::withMessages(['quantity' => 'The requested reduction exceeds available unreserved stock.']);
            }

            if ($type === 'correction' && $quantity < (int) $locked->reserved_quantity) {
                throw ValidationException::withMessages(['quantity' => 'Corrected stock cannot be lower than reserved stock.']);
            }

            if ((int) $locked->stock_quantity + $delta > 2147483647) {
                throw ValidationException::withMessages(['quantity' => 'The requested stock would exceed the supported quantity limit.']);
            }

            return InventoryAdjustmentRequest::query()->create([
                'shop_product_id' => $locked->id,
                'vendor_id' => $vendorUser->vendor_id,
                'requested_by' => $vendorUser->id,
                'change_type' => $type,
                'quantity' => $quantity,
                'stock_before' => (int) $locked->stock_quantity,
                'status' => 'pending',
                'reason' => $reason,
            ]);
        });

        $this->notifier->inventoryRequestSubmitted($request->load(['product', 'vendor']));

        return $request;
    }

    public function approve(InventoryAdjustmentRequest $request, User $reviewer): void
    {
        $this->assertReviewer($reviewer, $request);

        DB::transaction(function () use ($request, $reviewer) {
            $lockedRequest = InventoryAdjustmentRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($lockedRequest->status !== 'pending') {
                throw ValidationException::withMessages(['request' => 'This inventory request has already been reviewed.']);
            }

            $product = ShopProduct::query()->whereKey($lockedRequest->shop_product_id)->lockForUpdate()->firstOrFail();
            $delta = match ($lockedRequest->change_type) {
                'addition' => (int) $lockedRequest->quantity,
                'reduction' => -(int) $lockedRequest->quantity,
                'correction' => (int) $lockedRequest->quantity - (int) $product->stock_quantity,
                default => throw ValidationException::withMessages(['request' => 'This inventory request has an invalid change type.']),
            };

            if ($delta > 0 && (int) $product->stock_quantity + $delta > 2147483647) {
                throw ValidationException::withMessages(['request' => 'Approving this request would exceed the supported stock quantity limit.']);
            }

            try {
                $this->inventory->adjust(
                    $product,
                    $delta,
                    null,
                    $reviewer,
                    'Approved vendor request: '.$lockedRequest->reason,
                    $lockedRequest,
                );
            } catch (InventoryException $exception) {
                throw ValidationException::withMessages(['request' => $exception->getMessage()]);
            }

            $lockedRequest->quantity_delta = $delta;
            $lockedRequest->status = 'approved';
            $lockedRequest->reviewed_by = $reviewer->id;
            $lockedRequest->reviewed_at = now();
            $lockedRequest->save();
            AuditLogger::log($reviewer, 'inventory.request_approved', $lockedRequest, ['quantity_delta' => $delta]);
        });

        $this->notifier->inventoryRequestDecision($request->fresh(['requester', 'product']), 'approved', null);
    }

    public function reject(InventoryAdjustmentRequest $request, User $reviewer, string $reason): void
    {
        $this->assertReviewer($reviewer, $request);

        DB::transaction(function () use ($request, $reviewer, $reason) {
            $lockedRequest = InventoryAdjustmentRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if ($lockedRequest->status !== 'pending') {
                throw ValidationException::withMessages(['request' => 'This inventory request has already been reviewed.']);
            }

            $lockedRequest->status = 'rejected';
            $lockedRequest->review_note = $reason;
            $lockedRequest->reviewed_by = $reviewer->id;
            $lockedRequest->reviewed_at = now();
            $lockedRequest->save();
            AuditLogger::log($reviewer, 'inventory.request_rejected', $lockedRequest, ['reason' => $reason]);
        });

        $this->notifier->inventoryRequestDecision($request->fresh(['requester', 'product']), 'rejected', $reason);
    }

    private function assertReviewer(User $reviewer, InventoryAdjustmentRequest $request): void
    {
        abort_unless($reviewer->hasPermission('admin.access') && $reviewer->hasPermission('inventory.manage'), 403);

        if ($reviewer->vendor_id && (int) $reviewer->vendor_id === (int) $request->vendor_id) {
            abort(403);
        }
    }
}

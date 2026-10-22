<?php

namespace App\Services;

use App\Models\ProductApprovalHistory;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductWorkflowService
{
    public function __construct(private MarketplaceNotifier $notifier) {}

    public function createStaff(User $actor, array $data, bool $approveNow, bool $publishNow): ShopProduct
    {
        return DB::transaction(function () use ($actor, $data, $approveNow, $publishNow) {
            $product = new ShopProduct($this->staffAttributes($actor, $data));
            $product->created_by = $actor->id;
            $product->approval_status = 'pending_approval';
            $product->publication_status = 'unpublished';
            $product->slug = ShopProduct::uniqueSlug($product->product_name);
            $product->save();

            $this->history($product, $actor, 'created', null, 'pending_approval', null);
            AuditLogger::log($actor, 'product.created', $product, ['name' => $product->product_name]);

            if ($approveNow) {
                $this->approve($product, $actor, 'Approved during creation.');
                $product->refresh();
            }

            if ($publishNow) {
                $this->publish($product, $actor);
            }

            return $product->refresh();
        });
    }

    public function updateStaff(ShopProduct $product, User $actor, array $data): ShopProduct
    {
        return DB::transaction(function () use ($product, $actor, $data) {
            $attributes = $this->staffAttributes($actor, $data, $product);
            $this->apply($product, $actor, $attributes, false);
            AuditLogger::log($actor, 'product.updated', $product, ['fields' => array_keys($attributes)]);

            return $product->refresh();
        });
    }

    public function createVendor(User $actor, array $data, bool $submit): ShopProduct
    {
        $this->assertVendor($actor);

        return DB::transaction(function () use ($actor, $data, $submit) {
            $product = new ShopProduct($this->vendorAttributes($data));
            $product->vendor_id = $actor->vendor_id;
            $product->created_by = $actor->id;
            $product->publication_status = 'unpublished';
            $product->approval_status = $submit ? 'pending_approval' : 'draft';
            $product->slug = ShopProduct::uniqueSlug($product->product_name);

            if ($submit) {
                $product->submitted_by = $actor->id;
                $product->submitted_at = now();
            }

            $product->save();
            $this->history($product, $actor, $submit ? 'submitted' : 'drafted', null, $product->approval_status, null);

            if ($submit) {
                $this->notifier->productSubmitted($product);
            }

            return $product->refresh();
        });
    }

    public function updateVendor(ShopProduct $product, User $actor, array $data, bool $submit): ShopProduct
    {
        $this->assertOwns($actor, $product);

        return DB::transaction(function () use ($product, $actor, $data, $submit) {
            $this->apply($product, $actor, $this->vendorAttributes($data), true);

            if ($submit) {
                $this->submit($product, $actor);
            }

            return $product->refresh();
        });
    }

    public function submit(ShopProduct $product, User $actor): void
    {
        $this->assertOwns($actor, $product);

        if (! in_array($product->approval_status, ['draft', 'rejected', 'changes_requested', 'pending_approval'], true)
            && $product->revision_status !== 'pending_approval') {
            throw ValidationException::withMessages([
                'product' => 'This product cannot be submitted in its current state.',
            ]);
        }

        $from = $product->approval_status;
        $product->approval_status = 'pending_approval';
        $product->publication_status = $product->isPubliclyListed() ? $product->publication_status : 'unpublished';
        $product->submitted_by = $actor->id;
        $product->submitted_at = now();

        if ($product->pending_payload) {
            $product->revision_status = 'pending_approval';
        }

        $product->save();
        $this->history($product, $actor, 'submitted', $from, 'pending_approval', null);
        $this->notifier->productSubmitted($product);
    }

    public function approve(ShopProduct $product, User $actor, ?string $note): void
    {
        $this->assertReviewer($actor, $product);

        if ($product->pending_payload) {
            $product->fill($this->onlyCritical($product->pending_payload));
            $product->pending_payload = null;
            $product->revision_status = null;
        }

        $from = $product->approval_status;
        $product->approval_status = 'approved';
        $product->approved_by = $actor->id;
        $product->approved_at = now();
        $product->save();

        $this->history($product, $actor, 'approved', $from, 'approved', $note);
        AuditLogger::log($actor, 'product.approved', $product, ['note' => $note]);
        $this->notifier->productDecision($product, 'approved', $note);
    }

    public function reject(ShopProduct $product, User $actor, string $note): void
    {
        $this->assertReviewer($actor, $product);

        $from = $product->approval_status;
        $product->approval_status = 'rejected';
        $product->publication_status = 'unpublished';
        $product->pending_payload = null;
        $product->revision_status = null;
        $product->approved_by = $actor->id;
        $product->approved_at = now();
        $product->save();

        $this->history($product, $actor, 'rejected', $from, 'rejected', $note);
        AuditLogger::log($actor, 'product.rejected', $product, ['note' => $note]);
        $this->notifier->productDecision($product, 'rejected', $note);
    }

    public function requestChanges(ShopProduct $product, User $actor, string $note): void
    {
        $this->assertReviewer($actor, $product);

        $from = $product->approval_status;
        $product->approval_status = 'changes_requested';
        $product->publication_status = 'unpublished';
        $product->revision_status = null;
        $product->save();

        $this->history($product, $actor, 'changes_requested', $from, 'changes_requested', $note);
        AuditLogger::log($actor, 'product.changes_requested', $product, ['note' => $note]);
        $this->notifier->productDecision($product, 'changes requested', $note);
    }

    public function publish(ShopProduct $product, User $actor): void
    {
        if (! $actor->hasPermission('products.publish')) {
            abort(403);
        }

        $this->assertNotOwnVendor($actor, $product);

        if ($product->approval_status !== 'approved') {
            throw ValidationException::withMessages([
                'publication_status' => 'Approve the product before publishing it.',
            ]);
        }

        if ($product->revision_status === 'pending_approval') {
            throw ValidationException::withMessages([
                'publication_status' => 'Approve the pending changes before publishing.',
            ]);
        }

        $product->publication_status = 'published';
        $product->save();
        $this->history($product, $actor, 'published', $product->approval_status, 'published', null);
        AuditLogger::log($actor, 'product.published', $product);
    }

    public function unpublish(ShopProduct $product, User $actor): void
    {
        if (! $actor->hasPermission('products.publish')) {
            abort(403);
        }

        $product->publication_status = 'unpublished';
        $product->save();
        $this->history($product, $actor, 'unpublished', null, 'unpublished', null);
        AuditLogger::log($actor, 'product.unpublished', $product);
    }

    public function archive(ShopProduct $product, User $actor): void
    {
        if (! $actor->hasPermission('products.publish') && ! $actor->hasPermission('products.delete')) {
            abort(403);
        }

        $product->publication_status = 'archived';
        $product->save();
        $this->history($product, $actor, 'archived', null, 'archived', null);
        AuditLogger::log($actor, 'product.archived', $product);
    }

    private function apply(ShopProduct $product, User $actor, array $attributes, bool $vendorMode): void
    {
        $critical = config('marketplace.critical_product_fields');
        $live = $product->exists && $product->isPubliclyListed();
        $canApplyLive = ! $vendorMode && $actor->hasPermission('products.approve');

        if ($live && ! $canApplyLive) {
            $pending = $product->pending_payload ?? [];

            foreach ($critical as $field) {
                if (array_key_exists($field, $attributes)) {
                    $pending[$field] = $attributes[$field];
                    unset($attributes[$field]);
                }
            }

            $attributes['pending_payload'] = $pending;
            $attributes['revision_status'] = 'pending_approval';
        }

        if (! empty($attributes['product_name']) && $attributes['product_name'] !== $product->product_name) {
            $attributes['slug'] = ShopProduct::uniqueSlug($attributes['product_name'], $product->id);
        }

        $product->fill($attributes);
        $product->save();

        if (($attributes['revision_status'] ?? null) === 'pending_approval') {
            $this->history($product, $actor, 'revision_submitted', $product->approval_status, 'pending_approval', null);
            $this->notifier->productSubmitted($product);
        }
    }

    private function staffAttributes(User $actor, array $data, ?ShopProduct $existing = null): array
    {
        $attributes = $this->publicAttributes($data);

        if ($actor->hasPermission('products.assign_vendor')) {
            $attributes['vendor_id'] = $data['vendor_id'] ?? $existing?->vendor_id;
        }

        if ($actor->hasPermission('finance.view') && array_key_exists('acquisition_cost', $data)) {
            $attributes['acquisition_cost'] = $data['acquisition_cost'];
        }

        if ($actor->hasPermission('products.edit') || $actor->hasPermission('products.create')) {
            $attributes['internal_notes'] = $data['internal_notes'] ?? $existing?->internal_notes;
            $attributes['sku'] = ($data['sku'] ?? null) !== '' ? ($data['sku'] ?? $existing?->sku) : null;
            $attributes['product_type'] = $data['product_type'] ?? $existing?->product_type;
            $attributes['condition'] = $data['condition'] ?? $existing?->condition;
            $attributes['tracks_inventory'] = (bool) ($data['tracks_inventory'] ?? $existing?->tracks_inventory);
            $attributes['low_stock_threshold'] = $data['low_stock_threshold'] ?? $existing?->low_stock_threshold ?? 5;

            if (! $existing) {
                $attributes['stock_quantity'] = (int) ($data['stock_quantity'] ?? 0);
            }
        }

        return $attributes;
    }

    private function vendorAttributes(array $data): array
    {
        return $this->publicAttributes($data);
    }

    private function publicAttributes(array $data): array
    {
        return [
            'product_name' => $data['product_name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'working_days' => $data['working_days'],
            'status' => $data['status'],
            'is_popular' => (bool) ($data['is_popular'] ?? false),
            'features' => $data['features'] ?? [],
            'specifications' => $data['specifications'] ?? [],
            'thumbnail' => $data['thumbnail'] ?? null,
            'images' => $data['images'] ?? [],
            'shop_category_id' => $data['shop_category_id'],
            'subcategory_id' => $data['subcategory_id'] ?? null,
        ];
    }

    private function onlyCritical(array $payload): array
    {
        return array_intersect_key($payload, array_flip(config('marketplace.critical_product_fields')));
    }

    private function assertVendor(User $actor): void
    {
        if (! $actor->vendor_id || ! $actor->hasPermission('products.submit')) {
            abort(403);
        }
    }

    private function assertOwns(User $actor, ShopProduct $product): void
    {
        $this->assertVendor($actor);

        if ((int) $product->vendor_id !== (int) $actor->vendor_id) {
            abort(404);
        }
    }

    private function assertReviewer(User $actor, ShopProduct $product): void
    {
        if (! $actor->hasPermission('products.approve')) {
            abort(403);
        }

        $this->assertNotOwnVendor($actor, $product);
    }

    private function assertNotOwnVendor(User $actor, ShopProduct $product): void
    {
        if ($product->vendor_id && (int) $actor->vendor_id === (int) $product->vendor_id) {
            abort(403);
        }
    }

    private function history(ShopProduct $product, User $actor, string $action, ?string $from, ?string $to, ?string $note): void
    {
        ProductApprovalHistory::query()->create([
            'shop_product_id' => $product->id,
            'user_id' => $actor->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
        ]);
    }
}

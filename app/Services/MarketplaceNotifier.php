<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\InventoryAdjustmentRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductRequest;
use App\Models\ShopProduct;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\MarketplaceNotification;
use Illuminate\Support\Collection;

class MarketplaceNotifier
{
    public function staff(string $permission): Collection
    {
        return User::query()
            ->where(function ($query) use ($permission) {
                $query->whereHas('roles', fn ($roles) => $roles->where('slug', 'super-administrator'))
                    ->orWhereHas('roles.permissions', fn ($permissions) => $permissions->where('slug', $permission))
                    ->orWhereHas('directPermissions', fn ($permissions) => $permissions->where('slug', $permission));
            })
            ->get();
    }

    public function send(User|Customer $user, string $title, string $body, ?string $url, string $dedupeKey, bool $internal = true): void
    {
        if ($dedupeKey !== '' && $user->notifications()
            ->where('data->dedupe_key', $dedupeKey)
            ->where('created_at', '>', now()->subHours(12))
            ->exists()) {
            return;
        }

        $user->notify(new MarketplaceNotification($title, $body, $url, $dedupeKey, $internal));
    }

    public function productSubmitted(ShopProduct $product): void
    {
        foreach ($this->staff('products.approve') as $user) {
            $this->send(
                $user,
                'Product waiting for review',
                $product->product_name.' was submitted and needs a decision.',
                route('marketplace.approvals.index'),
                'product-submitted-'.$product->id,
            );
        }
    }

    public function productDecision(ShopProduct $product, string $decision, ?string $note): void
    {
        $ownerId = $product->submitted_by ?: $product->created_by;
        $owner = $ownerId ? User::query()->find($ownerId) : null;

        if (! $owner) {
            return;
        }

        $url = $owner->hasRole('vendor')
            ? route('vendor.products.index')
            : route('admin.shop-products.edit', $product);

        $this->send(
            $owner,
            'Product '.$decision,
            trim($product->product_name.' is now '.$decision.'. '.($note ?? '')),
            $url,
            'product-'.$decision.'-'.$product->id.'-'.$product->updated_at?->timestamp,
        );
    }

    public function requestAssigned(ProductRequest $request): void
    {
        $request->loadMissing('vendor.users');

        foreach ($request->vendor?->users ?? [] as $user) {
            $this->send(
                $user,
                'Request assigned to you',
                'Request '.$request->reference.' needs your response.',
                route('vendor.requests.show', $request->reference),
                'request-assigned-'.$request->id.'-'.$request->vendor_id,
            );
        }
    }

    public function requestNeedsStaff(ProductRequest $request, string $title): void
    {
        foreach ($this->staff('requests.view_all') as $user) {
            $this->send(
                $user,
                $title,
                'Request '.$request->reference.' needs attention.',
                route('marketplace.requests.show', $request),
                'request-staff-'.$request->id.'-'.$request->status,
            );
        }
    }

    public function orderAllocated(OrderItem $item): void
    {
        $item->loadMissing('vendor.users', 'order');

        foreach ($item->vendor?->users ?? [] as $user) {
            $this->send(
                $user,
                'Order allocated',
                'Order '.$item->order->reference.' includes an item assigned to you.',
                route('vendor.orders.show', $item->order->reference),
                'order-item-'.$item->id.'-vendor-'.$item->vendor_id,
            );
        }
    }

    public function hasVendorDeliveryRequest(Order $order, int $vendorId): bool
    {
        $dedupeKey = 'vendor-delivery-request-'.$order->id.'-'.$vendorId;

        return User::query()
            ->where('vendor_id', $vendorId)
            ->whereHas('notifications', fn ($notifications) => $notifications->where('data->dedupe_key', $dedupeKey))
            ->exists();
    }

    public function requestVendorDelivery(Order $order, Vendor $vendor): void
    {
        $order->loadMissing('items');
        $vendor->loadMissing('users');
        $itemCount = $order->items->where('vendor_id', $vendor->id)->count();

        foreach ($vendor->users as $user) {
            $this->send(
                $user,
                'Delivery requested',
                'Please review delivery details for order '.$order->reference.' and create a delivery for your assigned products when they are ready. '.$itemCount.' '.\Illuminate\Support\Str::plural('product', $itemCount).' assigned.',
                route('vendor.orders.show', $order->reference),
                'vendor-delivery-request-'.$order->id.'-'.$vendor->id,
            );
        }
    }

    public function customerOrder(Order $order, string $title, string $body): void
    {
        $order->loadMissing(['accountCustomer', 'user']);
        $customer = $order->accountCustomer ?? $order->user;

        if ($customer) {
            $this->send(
                $customer,
                $title,
                $body,
                route('account.orders.show', $order->reference),
                'order-'.$order->id.'-'.$order->fulfillment_status,
                false,
            );
        }
    }

    public function lowStock(ShopProduct $product): void
    {
        foreach ($this->staff('inventory.view') as $user) {
            $this->send(
                $user,
                'Low stock',
                $product->product_name.' is at or below its stock threshold.',
                route('marketplace.inventory.index'),
                'low-stock-'.$product->id.'-'.$product->availableQuantity(),
            );
        }
    }

    public function inventoryRequestSubmitted(InventoryAdjustmentRequest $request): void
    {
        foreach ($this->staff('inventory.manage')->filter(fn (User $user) => $user->hasPermission('admin.access')) as $user) {
            $this->send(
                $user,
                'Inventory change awaiting approval',
                $request->product->product_name.' has a pending vendor stock change request.',
                route('marketplace.inventory.index', ['request_status' => 'pending']),
                'inventory-request-'.$request->id.'-pending',
            );
        }
    }

    public function inventoryRequestDecision(InventoryAdjustmentRequest $request, string $decision, ?string $note): void
    {
        $owner = $request->requester;
        if (! $owner) {
            return;
        }

        $this->send(
            $owner,
            'Inventory request '.$decision,
            trim('The request for '.$request->product?->product_name.' was '.$decision.'. '.($note ?? '')),
            route('vendor.inventory'),
            'inventory-request-'.$request->id.'-'.$decision,
            false,
        );
    }
}

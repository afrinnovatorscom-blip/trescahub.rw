<?php

namespace App\Services;

use App\Exceptions\InventoryException;
use App\Models\DeliveryTask;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\ShopProduct;
use App\Models\User;
use App\Models\Vendor;
use App\Support\CustomerTracking;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private InventoryService $inventory,
        private MarketplaceNotifier $notifier,
    ) {}

    public function place(array $customer, array $lines, ?User $user): Order
    {
        if ($lines === []) {
            throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
        }

        return DB::transaction(function () use ($customer, $lines, $user) {
            $estimateDays = 1;
            $prepared = [];

            foreach ($lines as $line) {
                $product = ShopProduct::query()->whereKey($line['product']->id)->lockForUpdate()->firstOrFail();
                $quantity = (int) $line['quantity'];

                if (! $product->canPurchase($quantity)) {
                    throw new InventoryException($product->product_name.' is not available in the requested quantity.');
                }

                $estimateDays = max($estimateDays, (int) $product->working_days);
                $prepared[] = [$product, $quantity];
            }

            $order = Order::query()->create([
                'reference' => ReferenceGenerator::order(),
                'user_id' => $user?->id,
                'customer_name' => $customer['name'],
                'email' => $customer['email'] ?? null,
                'phone' => $customer['phone'],
                'delivery_address' => $customer['delivery_address'],
                'delivery_notes' => $customer['delivery_notes'] ?? null,
                'subtotal' => 0,
                'total' => 0,
                'payment_status' => 'pending',
                'payment_method' => $customer['payment_method'],
                'fulfillment_status' => 'pending_confirmation',
                'customer_status_message' => 'We have received your order.',
                'delivery_estimate_at' => now()->addDays($estimateDays),
            ]);

            $subtotal = 0;

            foreach ($prepared as [$product, $quantity]) {
                $location = $this->inventory->reserve($product, $quantity, $order, $user);
                $lineTotal = round((float) $product->price * $quantity, 2);
                $subtotal += $lineTotal;

                $item = OrderItem::query()->create([
                    'order_id' => $order->id,
                    'shop_product_id' => $product->id,
                    'vendor_id' => $product->vendor_id,
                    'product_location_id' => $location?->id,
                    'product_name' => $product->product_name,
                    'sku' => $product->sku,
                    'unit_price' => $product->price,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                    'fulfillment_status' => 'pending_confirmation',
                    'assignment_status' => $product->vendor_id ? 'assigned' : 'unassigned',
                    'inventory_state' => $product->tracks_inventory ? 'reserved' : 'none',
                ]);

                if ($product->vendor_id) {
                    $this->notifier->orderAllocated($item);
                }
            }

            $order->update(['subtotal' => $subtotal, 'total' => $subtotal]);
            $this->history($order, $user, null, 'pending_confirmation', null, 'We have received your order.');
            AuditLogger::log($user, 'order.created', $order, ['total' => $subtotal]);
            $this->notifier->customerOrder($order, 'Order received', 'We have received order '.$order->reference.'.');

            return $order->load('items');
        });
    }

    public function transition(Order $order, string $to, User $actor, ?string $note = null, ?string $customerMessage = null, ?string $cancellationReason = null): void
    {
        $from = $order->fulfillment_status;
        $this->assertTransition($from, $to);
        $this->assertActorCanTransition($order, $actor, $to);
        $order->loadMissing('items');

        DB::transaction(function () use ($order, $from, $to, $actor, $note, $customerMessage, $cancellationReason) {
            $message = $customerMessage ?: CustomerTracking::label($to);
            $order->fulfillment_status = $to;
            $order->customer_status_message = $message;

            if ($cancellationReason) {
                $order->cancellation_reason = $cancellationReason;
            }

            $order->save();

            foreach ($order->items as $item) {
                $item->fulfillment_status = $to;
                $item->save();
                $this->applyInventory($item, $to, $actor);
            }

            if (in_array($to, ['ready_for_dispatch', 'dispatched', 'out_for_delivery', 'delivered'], true)) {
                $this->syncDelivery($order, $actor, $to, $message);
            }

            $this->history($order, $actor, $from, $to, $note, $message);
            AuditLogger::log($actor, 'order.status_changed', $order, ['from' => $from, 'to' => $to]);
            $this->notifier->customerOrder($order, 'Order '.$order->reference, $message);
        });
    }

    public function requestCancellation(Order $order, User $customer, string $reason): void
    {
        if ((int) $order->user_id !== (int) $customer->id) {
            abort(404);
        }

        $order->loadMissing('items');
        $this->assertTransition($order->fulfillment_status, 'cancellation_requested');

        DB::transaction(function () use ($order, $customer, $reason) {
            $from = $order->fulfillment_status;
            $message = 'We have received your cancellation request.';
            $order->fulfillment_status = 'cancellation_requested';
            $order->customer_status_message = $message;
            $order->cancellation_reason = $reason;
            $order->save();

            foreach ($order->items as $item) {
                $item->fulfillment_status = 'cancellation_requested';
                $item->save();
            }

            $this->history($order, $customer, $from, 'cancellation_requested', $reason, $message);
            AuditLogger::log($customer, 'order.cancellation_requested', $order);
        });
    }

    public function updatePayment(Order $order, string $status, User $actor): void
    {
        if (! $actor->hasPermission('orders.manage')) {
            abort(403);
        }

        if (! in_array($status, ['pending', 'paid', 'failed', 'refunded'], true)) {
            throw ValidationException::withMessages(['payment_status' => 'Unknown payment status.']);
        }

        $from = $order->payment_status;
        $order->payment_status = $status;
        $order->save();
        AuditLogger::log($actor, 'order.payment_updated', $order, ['from' => $from, 'to' => $status]);
    }

    public function allocateItem(OrderItem $item, Vendor $vendor, User $actor): void
    {
        if (! $actor->hasPermission('orders.manage')) {
            abort(403);
        }

        if ($vendor->status !== 'active') {
            throw ValidationException::withMessages(['vendor_id' => 'Only an active vendor can receive an order.']);
        }

        $before = $item->vendor_id;
        $item->vendor_id = $vendor->id;
        $item->assignment_status = 'assigned';
        $item->save();

        AuditLogger::log($actor, 'order.item_allocated', $item, ['from_vendor' => $before, 'to_vendor' => $vendor->id]);
        $this->notifier->orderAllocated($item);
    }

    public function vendorDecision(OrderItem $item, User $actor, string $decision, ?string $note = null): void
    {
        $this->assertVendorItem($actor, $item);
        $item->loadMissing('order');

        if ($decision === 'accept') {
            $item->assignment_status = 'accepted';
            $item->save();
            AuditLogger::log($actor, 'order.item_accepted', $item);

            foreach ($this->notifier->staff('orders.manage') as $user) {
                $this->notifier->send(
                    $user,
                    'Vendor accepted an order',
                    'An item on order '.$item->order->reference.' was accepted.',
                    route('marketplace.orders.show', $item->order),
                    'order-accepted-'.$item->id,
                );
            }

            return;
        }

        if ($decision === 'decline') {
            $previous = $item->vendor_id;
            $item->vendor_id = null;
            $item->assignment_status = 'unassigned';
            $item->internal_notes = trim(($item->internal_notes ? $item->internal_notes."\n" : '').($note ?? ''));
            $item->save();
            AuditLogger::log($actor, 'order.item_declined', $item, ['vendor_id' => $previous]);

            foreach ($this->notifier->staff('orders.manage') as $user) {
                $this->notifier->send(
                    $user,
                    'Order needs reassignment',
                    'An item on order '.$item->order->reference.' was declined and is back in the queue.',
                    route('marketplace.orders.show', $item->order),
                    'order-declined-'.$item->id.'-'.$previous,
                );
            }

            return;
        }

        $item->assignment_status = 'clarification';
        $item->internal_notes = trim(($item->internal_notes ? $item->internal_notes."\n" : '').($note ?? ''));
        $item->save();
        AuditLogger::log($actor, 'order.item_clarification', $item);
    }

    public function vendorProgress(OrderItem $item, User $actor, string $to, ?string $note = null): void
    {
        $this->assertVendorItem($actor, $item);

        $allowed = config('marketplace.vendor_item_transitions.'.$item->fulfillment_status, []);

        if (! in_array($to, $allowed, true)) {
            throw ValidationException::withMessages(['status' => 'That fulfillment update is not available.']);
        }

        DB::transaction(function () use ($item, $actor, $to, $note) {
            $item->fulfillment_status = $to;
            if ($note) {
                $item->fulfillment_instructions = $note;
            }
            $item->save();
            $this->syncParentFromItems($item->order, $actor);
            AuditLogger::log($actor, 'order.item_progress', $item, ['to' => $to]);
        });
    }

    public function assignDeliveryOfficer(Order $order, User $officer, User $actor): void
    {
        if (! $actor->hasPermission('deliveries.manage') && ! $actor->hasPermission('orders.manage')) {
            abort(403);
        }

        $before = $order->delivery_officer_id;
        $order->delivery_officer_id = $officer->id;
        $order->save();

        $task = $this->ensureDeliveryTask($order, $actor);
        $task->delivery_officer_id = $officer->id;
        $task->save();

        AuditLogger::log($actor, 'delivery.reassigned', $task, ['from' => $before, 'to' => $officer->id]);
    }

    private function syncParentFromItems(Order $order, User $actor): void
    {
        $order->load('items');
        $rank = [
            'pending_confirmation' => 0,
            'confirmed' => 1,
            'awaiting_stock' => 2,
            'processing' => 3,
            'ready_for_dispatch' => 4,
            'dispatched' => 5,
            'out_for_delivery' => 6,
            'delivered' => 7,
            'completed' => 8,
            'cancellation_requested' => 1,
            'cancelled' => 9,
            'return_requested' => 8,
            'returned' => 9,
        ];

        $active = $order->items->reject(fn (OrderItem $item) => $item->fulfillment_status === 'cancelled');
        $pool = $active->isEmpty() ? $order->items : $active;
        $next = $pool->sortBy(fn (OrderItem $item) => $rank[$item->fulfillment_status] ?? 0)->first()?->fulfillment_status;

        if (! $next || $next === $order->fulfillment_status) {
            return;
        }

        $from = $order->fulfillment_status;
        $message = CustomerTracking::label($next);
        $order->fulfillment_status = $next;
        $order->customer_status_message = $message;
        $order->save();
        $this->history($order, $actor, $from, $next, 'Updated from item progress.', $message);
        $this->notifier->customerOrder($order, 'Order '.$order->reference, $message);
    }

    private function applyInventory(OrderItem $item, string $status, User $actor): void
    {
        if (in_array($status, ['cancelled'], true)) {
            $this->inventory->release($item, $actor);
        }

        if (in_array($status, ['delivered', 'completed'], true)) {
            $this->inventory->commitSale($item, $actor);
        }

        if ($status === 'returned') {
            $this->inventory->restock($item, $actor);
        }
    }

    private function syncDelivery(Order $order, User $actor, string $status, string $message): void
    {
        $task = $this->ensureDeliveryTask($order, $actor);
        $task->status = match ($status) {
            'dispatched' => 'in_transit',
            'out_for_delivery' => 'in_transit',
            'delivered' => 'delivered',
            default => $task->status,
        };
        $task->customer_message = $message;

        if ($status === 'dispatched' && ! $task->dispatched_at) {
            $task->dispatched_at = now();
        }

        if ($status === 'delivered') {
            $task->delivered_at = now();
            AuditLogger::log($actor, 'delivery.completed', $task);
        }

        $task->save();
    }

    private function ensureDeliveryTask(Order $order, User $actor): DeliveryTask
    {
        $existing = $order->deliveries()->latest('id')->first();

        if ($existing) {
            return $existing;
        }

        $item = $order->items()->first();

        return DeliveryTask::query()->create([
            'reference' => ReferenceGenerator::delivery(),
            'order_id' => $order->id,
            'order_item_id' => $item?->id,
            'vendor_id' => $item?->vendor_id,
            'product_location_id' => $item?->product_location_id,
            'assigned_officer_id' => $order->assigned_officer_id,
            'delivery_officer_id' => $order->delivery_officer_id ?: ($actor->hasPermission('deliveries.manage') ? $actor->id : null),
            'status' => 'assigned',
            'fulfillment_instructions' => $item?->fulfillment_instructions,
        ]);
    }

    private function assertTransition(string $from, string $to): void
    {
        $allowed = config('marketplace.order_transitions.'.$from, []);

        if (! in_array($to, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => 'That order status change is not allowed.',
            ]);
        }
    }

    private function assertActorCanTransition(Order $order, User $actor, string $to): void
    {
        if ($actor->hasPermission('orders.manage')) {
            return;
        }

        $deliveryMove = in_array($to, ['dispatched', 'out_for_delivery', 'delivered'], true);

        if ($actor->hasPermission('deliveries.manage') && $deliveryMove) {
            if ($actor->hasPermission('orders.view_all') || (int) $order->delivery_officer_id === (int) $actor->id) {
                return;
            }
        }

        abort(403);
    }

    private function assertVendorItem(User $actor, OrderItem $item): void
    {
        if (! $actor->vendor_id || (int) $item->vendor_id !== (int) $actor->vendor_id) {
            abort(404);
        }
    }

    private function history(Order $order, ?User $actor, ?string $from, string $to, ?string $note, ?string $customerMessage): void
    {
        OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'user_id' => $actor?->id,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'customer_message' => $customerMessage,
        ]);
    }
}

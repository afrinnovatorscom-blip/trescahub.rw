<?php

namespace App\Services;

use App\Exceptions\InventoryException;
use App\Models\Customer;
use App\Models\DeliveryTask;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\OrderStatusHistory;
use App\Models\ShopProduct;
use App\Models\User;
use App\Models\Vendor;
use App\Support\CustomerTracking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private InventoryService $inventory,
        private MarketplaceNotifier $notifier,
    ) {}

    public function place(
        array $customer,
        array $lines,
        ?User $user,
        ?Customer $accountCustomer = null,
        string $source = 'cart_checkout',
        ?string $submissionKey = null,
    ): Order {
        if ($submissionKey !== null) {
            $existingOrder = Order::query()->where('submission_key', $submissionKey)->first();

            if ($existingOrder) {
                if ($existingOrder->source !== $source) {
                    throw ValidationException::withMessages([
                        'submission_key' => 'This submission key has already been used.',
                    ]);
                }

                return $existingOrder->load('items');
            }
        }

        if ($lines === []) {
            throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
        }

        return DB::transaction(function () use ($customer, $lines, $user, $accountCustomer, $source, $submissionKey) {
            if ($submissionKey !== null) {
                $existingOrder = Order::query()->where('submission_key', $submissionKey)->first();

                if ($existingOrder) {
                    if ($existingOrder->source !== $source) {
                        throw ValidationException::withMessages([
                            'submission_key' => 'This submission key has already been used.',
                        ]);
                    }

                    return $existingOrder->load('items');
                }
            }

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
                'source' => $source,
                'submission_key' => $submissionKey,
                'user_id' => $user?->id,
                'customer_id' => $accountCustomer?->id,
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

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'shop_product_id' => $product->id,
                    'vendor_id' => null,
                    'product_location_id' => $location?->id,
                    'product_name' => $product->product_name,
                    'sku' => $product->sku,
                    'unit_price' => $product->price,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                    'fulfillment_status' => 'pending_confirmation',
                    'assignment_status' => 'unassigned',
                    'inventory_state' => $product->tracks_inventory ? 'reserved' : 'none',
                ]);
            }

            $order->update(['subtotal' => $subtotal, 'total' => $subtotal]);
            $this->history($order, $user, null, 'pending_confirmation', null, 'We have received your order.');
            AuditLogger::log($user, 'order.created', $order, ['total' => $subtotal]);
            $this->notifier->customerOrder($order, 'Order received', 'We have received order '.$order->reference.'.');

            return $order->load('items');
        });
    }

    public function transition(
        Order $order,
        string $to,
        User $actor,
        ?string $note = null,
        ?string $customerMessage = null,
        ?string $cancellationReason = null,
        bool $notifyCustomer = true,
        bool $reviewDecision = false,
        ?DeliveryTask $deliveryTask = null,
    ): void {
        $from = $order->fulfillment_status;
        if ($deliveryTask && (int) $deliveryTask->order_id !== (int) $order->id) {
            throw ValidationException::withMessages([
                'delivery' => 'The selected delivery does not belong to this order.',
            ]);
        }
        if ($from === 'pending_confirmation' && ! $reviewDecision) {
            throw ValidationException::withMessages(['decision' => 'Use the order review decision before changing this order.']);
        }
        if ($from === 'pending_confirmation' && $to === 'cancelled' && ! trim((string) $cancellationReason)) {
            throw ValidationException::withMessages(['cancellation_reason' => 'A rejection reason is required.']);
        }
        $this->assertTransition($from, $to);
        $this->assertActorCanTransition($order, $actor, $to);
        if ($to === 'ready_for_dispatch' && $order->items()->whereNotNull('vendor_id')->exists()) {
            throw ValidationException::withMessages([
                'fulfillment_status' => 'Mark each vendor assignment Ready for Delivery instead of changing the overall order status.',
            ]);
        }
        $order->loadMissing('items');

        DB::transaction(function () use ($order, $from, $to, $actor, $note, $customerMessage, $cancellationReason, $notifyCustomer, $deliveryTask) {
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

            if (in_array($to, ['dispatched', 'out_for_delivery', 'delivered'], true)) {
                $this->syncDelivery($order, $actor, $to, $message, $deliveryTask);
            }

            $this->history($order, $actor, $from, $to, $note, $message);
            AuditLogger::log($actor, 'order.status_changed', $order, ['from' => $from, 'to' => $to]);
            if ($notifyCustomer) {
                $this->notifier->customerOrder($order, 'Order '.$order->reference, $message);
            }
        });
    }

    public function reviewOrder(Order $order, User $actor, string $decision, ?string $reason = null, ?string $note = null): void
    {
        if (! $actor->hasPermission('orders.manage')) {
            abort(403);
        }

        if (! in_array($decision, ['reject', 'terminate'], true)) {
            throw ValidationException::withMessages(['decision' => 'Order acceptance must be saved with allocation and payment details.']);
        }

        if (! trim((string) $reason)) {
            throw ValidationException::withMessages(['reason' => 'A rejection reason is required.']);
        }

        DB::transaction(function () use ($order, $actor, $reason) {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($lockedOrder->fulfillment_status !== 'pending_confirmation') {
                throw ValidationException::withMessages(['decision' => 'This order has already left the review stage.']);
            }
            $items = $lockedOrder->items()->lockForUpdate()->get();
            if ($items->isEmpty() || $items->contains(
                fn (OrderItem $item) => $item->assignment_status !== 'admin_handled' || $item->vendor_id !== null
            )) {
                throw ValidationException::withMessages([
                    'decision' => 'Only orders allocated entirely to Admin can be rejected in the Admin review workflow.',
                ]);
            }

            $rejectionReason = trim((string) $reason);
            $auditNote = 'Admin rejection: '.$rejectionReason;
            $this->transition($lockedOrder, 'cancelled', $actor, $auditNote, 'Your order was rejected.', $auditNote, false, true);
        });
    }

    public function requestCancellation(Order $order, User|Customer $customer, string $reason): void
    {
        $ownsOrder = $customer instanceof Customer
            ? (int) $order->customer_id === (int) $customer->id
            : (int) $order->user_id === (int) $customer->id;

        if (! $ownsOrder) {
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

            $actor = $customer instanceof User ? $customer : null;
            $this->history($order, $actor, $from, 'cancellation_requested', $reason, $message);
            AuditLogger::log($actor, 'order.cancellation_requested', $order);
        });
    }

    public function saveProcessingChanges(
        Order $order,
        User $actor,
        array $allocations,
        bool $recordPayment,
        ?string $paymentType = null,
        ?string $paymentMethod = null,
        ?float $partialAmount = null,
        ?string $submissionKey = null,
        bool $acceptOrder = false,
        bool $allocateWholeOrder = false,
    ): void {
        if (! $actor->hasPermission('orders.manage')) {
            abort(403);
        }

        if (($allocations !== [] || $acceptOrder) && ! $actor->hasPermission('vendors.view')) {
            abort(403);
        }

        DB::transaction(function () use ($order, $actor, $allocations, $recordPayment, $paymentType, $paymentMethod, $partialAmount, $submissionKey, $acceptOrder, $allocateWholeOrder) {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($allocateWholeOrder && $lockedOrder->fulfillment_status !== 'pending_confirmation') {
                throw ValidationException::withMessages([
                    'allocations' => 'This order is no longer awaiting allocation.',
                ]);
            }
            if ($acceptOrder && $lockedOrder->fulfillment_status !== 'pending_confirmation') {
                throw ValidationException::withMessages(['accept_order' => 'This order is no longer awaiting acceptance.']);
            }
            if (! $acceptOrder
                && in_array($lockedOrder->fulfillment_status, ['pending_confirmation', 'cancelled', 'returned'], true)
                && ! ($lockedOrder->fulfillment_status === 'pending_confirmation' && $allocations !== [])) {
                throw ValidationException::withMessages(['order' => 'Only accepted, active orders can be edited.']);
            }
            if ($acceptOrder && ! $lockedOrder->items()->exists()) {
                throw ValidationException::withMessages(['allocations' => 'An order without products cannot be accepted through this form.']);
            }
            if ($acceptOrder && $recordPayment) {
                throw ValidationException::withMessages([
                    'has_paid' => 'Save the order acceptance and allocation before recording payment.',
                ]);
            }

            if ($recordPayment && $submissionKey) {
                $previousSubmission = OrderPayment::query()->where('submission_key', $submissionKey)->first();
                if ($previousSubmission) {
                    if ((int) $previousSubmission->order_id !== (int) $lockedOrder->id) {
                        throw ValidationException::withMessages(['submission_key' => 'This payment submission key has already been used.']);
                    }

                    return;
                }
            }

            $items = $lockedOrder->items()->lockForUpdate()->get();
            if ($allocateWholeOrder) {
                if ($recordPayment) {
                    throw ValidationException::withMessages([
                        'has_paid' => 'Save the order allocation before recording payment.',
                    ]);
                }
                if ($acceptOrder) {
                    throw ValidationException::withMessages([
                        'accept_order' => 'Save the order allocation before accepting it.',
                    ]);
                }
                if (count($allocations) !== $items->count()) {
                    throw ValidationException::withMessages([
                        'allocations' => 'Choose one allocation for the entire order.',
                    ]);
                }

                $selectedAssignments = array_unique(array_map('strval', array_values($allocations)));
                if (count($selectedAssignments) !== 1) {
                    throw ValidationException::withMessages([
                        'allocations' => 'Every product in this order must have the same allocation.',
                    ]);
                }

                $selectedAssignment = reset($selectedAssignments);
                if ($items->contains(
                    fn (OrderItem $item) => $item->vendor_id !== null || $item->assignment_status !== 'unassigned'
                )) {
                    throw ValidationException::withMessages([
                        'allocations' => 'This order has already been allocated.',
                    ]);
                }
                foreach ($allocations as $itemId => $value) {
                    if (! $items->contains(fn (OrderItem $item) => (int) $item->id === (int) $itemId)) {
                        throw ValidationException::withMessages([
                            'allocations' => 'An allocation references an item outside this order.',
                        ]);
                    }
                    if ((string) $value !== $selectedAssignment) {
                        throw ValidationException::withMessages([
                            'allocations' => 'Every product in this order must have the same allocation.',
                        ]);
                    }
                }

                $vendorId = $selectedAssignment === 'admin'
                    ? null
                    : filter_var($selectedAssignment, FILTER_VALIDATE_INT);
                if ($selectedAssignment !== 'admin' && ($vendorId === false || ! Vendor::query()->whereKey($vendorId)->where('status', 'active')->exists())) {
                    throw ValidationException::withMessages([
                        'allocations' => 'Choose an active vendor or Handle by Admin.',
                    ]);
                }

                foreach ($items as $item) {
                    $previousVendorId = $item->vendor_id;
                    $previousAssignmentStatus = $item->assignment_status;
                    $item->vendor_id = $vendorId;
                    $item->assignment_status = $selectedAssignment === 'admin' ? 'admin_handled' : 'assigned';
                    $item->save();
                    AuditLogger::log($actor, 'order.item_allocated', $item, [
                        'from_vendor' => $previousVendorId,
                        'to_vendor' => $vendorId,
                        'from_assignment_status' => $previousAssignmentStatus,
                        'to_assignment_status' => $item->assignment_status,
                    ]);

                    if ($vendorId !== null) {
                        $this->notifier->orderAllocated($item);
                    }
                }

                if ($vendorId !== null) {
                    $this->transition($lockedOrder, 'confirmed', $actor, null, null, null, true, true);
                }

                return;
            }

            if ($recordPayment) {
                $allItemsHandledByAdmin = $items->isNotEmpty()
                    && $items->every(
                        fn (OrderItem $item) => $item->assignment_status === 'admin_handled' && $item->vendor_id === null
                    );
                if (! $allItemsHandledByAdmin) {
                    throw ValidationException::withMessages([
                        'has_paid' => 'Admin can record payment only after every product is saved as Handled by Admin.',
                    ]);
                }

                foreach ($allocations as $itemId => $selectedVendorId) {
                    $item = $items->firstWhere('id', (int) $itemId);
                    $isSameAllocation = $item
                        && (($selectedVendorId === 'admin' && $item->assignment_status === 'admin_handled' && $item->vendor_id === null)
                            || ($selectedVendorId !== 'admin'
                                && filter_var($selectedVendorId, FILTER_VALIDATE_INT) !== false
                                && (int) $item->vendor_id === (int) $selectedVendorId));
                    if (! $isSameAllocation) {
                        throw ValidationException::withMessages([
                            'allocations' => 'Save allocation changes before recording payment.',
                        ]);
                    }
                }
            }
            $canAllocate = $acceptOrder || in_array($lockedOrder->fulfillment_status, [
                'confirmed', 'processing', 'awaiting_stock', 'ready_for_dispatch',
            ], true);

            if ($acceptOrder) {
                if ($allocations !== []) {
                    throw ValidationException::withMessages([
                        'allocations' => 'Save a single order allocation before accepting the order.',
                    ]);
                }
                if ($items->isEmpty() || $items->contains(
                    fn (OrderItem $item) => $item->assignment_status !== 'admin_handled' || $item->vendor_id !== null
                )) {
                    throw ValidationException::withMessages([
                        'accept_order' => 'Only an order allocated entirely to Admin can be accepted through the Admin review workflow.',
                    ]);
                }
            }

            foreach ($allocations as $itemId => $selectedVendorId) {
                $item = $items->firstWhere('id', (int) $itemId);
                if (! $item) {
                    throw ValidationException::withMessages(['allocations' => 'An allocation references an item outside this order.']);
                }

                if (! $canAllocate) {
                    throw ValidationException::withMessages(['allocations' => 'This order is not currently accepting allocation changes.']);
                }

                if ($acceptOrder && ! $selectedVendorId) {
                    throw ValidationException::withMessages(['allocations.'.$itemId => 'Choose a vendor or Handled by Admin for every product.']);
                }
                $handledByAdmin = $selectedVendorId === 'admin';
                $vendorId = in_array($selectedVendorId, ['unassigned', 'admin'], true) ? null : filter_var($selectedVendorId, FILTER_VALIDATE_INT);
                if ($vendorId === false) {
                    throw ValidationException::withMessages(['allocations.'.$itemId => 'Choose a valid vendor or Handled by Admin.']);
                }
                if ($acceptOrder && $selectedVendorId === 'unassigned') {
                    throw ValidationException::withMessages(['allocations.'.$itemId => 'Choose a vendor or Handled by Admin for every product.']);
                }
                if ($vendorId !== null) {
                    $vendor = Vendor::query()->whereKey($vendorId)->where('status', 'active')->first();
                    if (! $vendor) {
                        throw ValidationException::withMessages(['allocations.'.$itemId => 'Choose an active vendor.']);
                    }
                }

                $newAssignmentStatus = $handledByAdmin ? 'admin_handled' : ($vendorId === null ? 'unassigned' : 'assigned');
                if (($vendorId !== null && (int) $item->vendor_id === $vendorId)
                    || ($vendorId === null && $item->vendor_id === null && $item->assignment_status === $newAssignmentStatus)) {
                    continue;
                }

                $previousVendorId = $item->vendor_id;
                $previousAssignmentStatus = $item->assignment_status;
                $item->vendor_id = $vendorId;
                $item->assignment_status = $newAssignmentStatus;
                $item->save();
                AuditLogger::log($actor, 'order.item_allocated', $item, [
                    'from_vendor' => $previousVendorId,
                    'to_vendor' => $vendorId,
                    'from_assignment_status' => $previousAssignmentStatus,
                    'to_assignment_status' => $newAssignmentStatus,
                ]);

                if ($vendorId !== null) {
                    $this->notifier->orderAllocated($item);
                }
            }

            if (! $recordPayment) {
                if ($acceptOrder) {
                    $this->transition($lockedOrder, 'confirmed', $actor, null, null, null, true, true);
                }

                return;
            }

            if (! in_array($paymentType, ['full', 'partial'], true)) {
                throw ValidationException::withMessages(['payment_type' => 'Choose full or partial payment.']);
            }

            if (! in_array($paymentMethod, ['cash_on_delivery', 'bank_transfer'], true)) {
                throw ValidationException::withMessages(['payment_method' => 'Choose a supported payment method.']);
            }

            $recordedAmount = (int) round((float) $lockedOrder->payments()->sum('amount') * 100);
            if ($recordedAmount === 0 && $lockedOrder->payment_status === 'paid') {
                $recordedAmount = (int) round((float) $lockedOrder->total * 100);
            }

            $totalAmount = (int) round((float) $lockedOrder->total * 100);
            $outstanding = max(0, $totalAmount - $recordedAmount);
            if ($outstanding === 0) {
                if ($acceptOrder) {
                    $this->transition($lockedOrder, 'confirmed', $actor, null, null, null, true, true);
                }

                return;
            }

            $amount = $paymentType === 'full'
                ? $outstanding
                : (int) round((float) $partialAmount * 100);

            if ($amount <= 0 || ($paymentType === 'partial' && $amount >= $outstanding)) {
                throw ValidationException::withMessages([
                    'partial_amount' => 'Enter a positive payment amount less than the outstanding balance. Use full payment to record the remaining balance.',
                ]);
            }

            OrderPayment::query()->create([
                'order_id' => $lockedOrder->id,
                'recorded_by' => $actor->id,
                'amount' => number_format($amount / 100, 2, '.', ''),
                'type' => $paymentType,
                'method' => $paymentMethod,
                'submission_key' => $submissionKey,
            ]);

            $newTotalPaid = $recordedAmount + $amount;
            $previousStatus = $lockedOrder->payment_status;
            $lockedOrder->payment_status = $newTotalPaid >= $totalAmount ? 'paid' : 'partially_paid';
            $lockedOrder->payment_method = $paymentMethod;
            $lockedOrder->save();
            AuditLogger::log($actor, 'order.payment_recorded', $lockedOrder, [
                'amount' => number_format($amount / 100, 2, '.', ''),
                'type' => $paymentType,
                'method' => $paymentMethod,
                'from_status' => $previousStatus,
                'to_status' => $lockedOrder->payment_status,
            ]);

            if ($acceptOrder) {
                $this->transition($lockedOrder, 'confirmed', $actor, null, null, null, true, true);
            }
        });
    }

    public function vendorDecision(OrderItem $item, User $actor, string $decision, ?string $note = null): void
    {
        $this->assertVendorItem($actor, $item);
        if (! in_array($decision, ['accept', 'decline', 'clarification', 'problem'], true)) {
            throw ValidationException::withMessages(['decision' => 'That assignment response is not available.']);
        }
        if (in_array($decision, ['problem', 'clarification'], true) && ! trim((string) $note)) {
            throw ValidationException::withMessages(['note' => 'A reason is required to report an assignment problem.']);
        }

        DB::transaction(function () use ($item, $actor, $decision, $note) {
            $lockedItem = OrderItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $this->assertVendorItem($actor, $lockedItem);
            $lockedItem->load('order');

            if ($lockedItem->order->fulfillment_status === 'pending_confirmation') {
                throw ValidationException::withMessages(['decision' => 'The customer order must be accepted by an administrator first.']);
            }
            if ($decision === 'accept' && ! in_array($lockedItem->order->fulfillment_status, [
                'confirmed', 'processing', 'awaiting_stock', 'ready_for_dispatch',
            ], true)) {
                throw ValidationException::withMessages(['decision' => 'This order is not in a state that accepts vendor assignments.']);
            }
            if (in_array($lockedItem->order->fulfillment_status, ['delivered', 'completed', 'cancelled', 'returned'], true)
                || in_array($lockedItem->fulfillment_status, ['delivered', 'completed', 'cancelled', 'returned'], true)) {
                throw ValidationException::withMessages(['decision' => 'Completed or closed assignments cannot be changed.']);
            }

            if ($decision === 'accept' && $lockedItem->assignment_status !== 'assigned') {
                throw ValidationException::withMessages(['decision' => 'This assignment is no longer awaiting acceptance.']);
            }
            if (in_array($decision, ['decline', 'clarification'], true)
                && ! in_array($lockedItem->assignment_status, ['assigned', 'clarification'], true)) {
                throw ValidationException::withMessages(['decision' => 'This assignment is no longer awaiting a response.']);
            }
            if ($decision === 'problem' && ! in_array($lockedItem->assignment_status, ['assigned', 'accepted'], true)) {
                throw ValidationException::withMessages(['decision' => 'This assignment already has a reported issue or is no longer active.']);
            }

            if ($decision === 'accept') {
                $lockedItem->assignment_status = 'accepted';
                $lockedItem->save();
                AuditLogger::log($actor, 'order.item_accepted', $lockedItem);

                foreach ($this->notifier->staff('orders.manage') as $user) {
                    $this->notifier->send(
                        $user,
                        'Vendor accepted an order',
                        'An item on order '.$lockedItem->order->reference.' was accepted.',
                        route('marketplace.orders.show', $lockedItem->order),
                        'order-accepted-'.$lockedItem->id,
                    );
                }

                return;
            }

            if ($decision === 'decline') {
                $previous = $lockedItem->vendor_id;
                $lockedItem->vendor_id = null;
                $lockedItem->assignment_status = 'unassigned';
                $lockedItem->internal_notes = trim(($lockedItem->internal_notes ? $lockedItem->internal_notes."\n" : '').($note ?? ''));
                $lockedItem->save();
                AuditLogger::log($actor, 'order.item_declined', $lockedItem, ['vendor_id' => $previous]);

                foreach ($this->notifier->staff('orders.manage') as $user) {
                    $this->notifier->send(
                        $user,
                        'Order needs reassignment',
                        'An item on order '.$lockedItem->order->reference.' was declined and is back in the queue.',
                        route('marketplace.orders.show', $lockedItem->order),
                        'order-declined-'.$lockedItem->id.'-'.$previous,
                    );
                }

                return;
            }

            $lockedItem->assignment_status = 'clarification';
            $lockedItem->internal_notes = trim(($lockedItem->internal_notes ? $lockedItem->internal_notes."\n" : '').($note ?? ''));
            $lockedItem->save();
            AuditLogger::log($actor, 'order.item_problem_reported', $lockedItem, ['note' => $note]);

            foreach ($this->notifier->staff('orders.manage') as $user) {
                $this->notifier->send(
                    $user,
                    'Vendor reported an order problem',
                    $lockedItem->product_name.' on order '.$lockedItem->order->reference.': '.trim((string) $note),
                    route('marketplace.orders.show', $lockedItem->order),
                    'order-problem-'.$lockedItem->id.'-'.$lockedItem->updated_at?->timestamp,
                );
            }
        });
    }

    public function vendorProgress(OrderItem $item, User $actor, string $to, ?string $note = null): void
    {
        $this->assertVendorItem($actor, $item);
        if ($to === 'ready_for_dispatch') {
            throw ValidationException::withMessages([
                'fulfillment_status' => 'Use the Ready for Delivery action for the complete vendor assignment.',
            ]);
        }

        DB::transaction(function () use ($item, $actor, $to, $note) {
            $lockedItem = OrderItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $this->assertVendorItem($actor, $lockedItem);
            $lockedItem->load('order');

            if (in_array($lockedItem->fulfillment_status, ['delivered', 'completed', 'cancelled', 'returned'], true)) {
                throw ValidationException::withMessages(['fulfillment_status' => 'Completed or closed assignments cannot be changed.']);
            }
            if ($lockedItem->assignment_status !== 'accepted') {
                throw ValidationException::withMessages(['fulfillment_status' => 'Accept this assignment before updating its fulfillment.']);
            }
            if ($lockedItem->order->fulfillment_status === 'pending_confirmation') {
                throw ValidationException::withMessages(['fulfillment_status' => 'The customer order must be accepted by an administrator first.']);
            }
            if (in_array($lockedItem->order->fulfillment_status, ['delivered', 'completed', 'cancelled', 'returned'], true)) {
                throw ValidationException::withMessages(['fulfillment_status' => 'Completed or closed assignments cannot be changed.']);
            }

            $allowed = config('marketplace.vendor_item_transitions.'.$lockedItem->fulfillment_status, []);
            if (! in_array($to, $allowed, true)) {
                throw ValidationException::withMessages(['fulfillment_status' => 'That fulfillment update is not available.']);
            }

            $lockedItem->fulfillment_status = $to;
            if ($note) {
                $lockedItem->internal_notes = trim(($lockedItem->internal_notes ? $lockedItem->internal_notes."\n" : '').$note);
            }
            $lockedItem->save();
            $this->syncParentFromItems($lockedItem->order, $actor);
            AuditLogger::log($actor, 'order.item_progress', $lockedItem, ['to' => $to]);
        });
    }

    public function markVendorAssignmentReady(Order $order, int $vendorId, User $actor): void
    {
        if ($actor->hasPermission('orders.view_assigned') && $actor->vendor_id) {
            abort_unless((int) $actor->vendor_id === $vendorId, 404);
        } elseif (! $actor->hasPermission('orders.manage') || ! $actor->hasPermission('vendors.view')) {
            abort(403);
        }

        DB::transaction(function () use ($order, $vendorId, $actor) {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (in_array($lockedOrder->fulfillment_status, [
                'pending_confirmation', 'delivered', 'completed', 'cancelled', 'returned',
            ], true)) {
                throw ValidationException::withMessages([
                    'fulfillment_status' => 'This order cannot accept a vendor readiness update in its current state.',
                ]);
            }
            if ($vendorId === null && $lockedOrder->payment_status !== 'paid') {
                throw ValidationException::withMessages([
                    'delivery' => 'Payment must be recorded before preparing an Admin-managed delivery.',
                ]);
            }

            $items = OrderItem::query()
                ->where('order_id', $lockedOrder->id)
                ->where('vendor_id', $vendorId)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            if ($items->isEmpty()) {
                abort(404);
            }

            foreach ($items as $item) {
                if ($item->assignment_status === 'clarification') {
                    throw ValidationException::withMessages([
                        'fulfillment_status' => 'Resolve the reported fulfillment problem before marking this assignment Ready for Delivery.',
                    ]);
                }
                if ($item->assignment_status !== 'accepted'
                    || ! in_array($item->fulfillment_status, ['processing', 'ready_for_dispatch'], true)) {
                    throw ValidationException::withMessages([
                        'fulfillment_status' => 'Every product in this vendor assignment must be accepted and prepared before it can be marked Ready for Delivery.',
                    ]);
                }
            }

            foreach ($items as $item) {
                if ($item->fulfillment_status === 'ready_for_dispatch') {
                    continue;
                }

                $item->fulfillment_status = 'ready_for_dispatch';
                $item->save();
                AuditLogger::log($actor, 'order.item_progress', $item, [
                    'from' => 'processing',
                    'to' => 'ready_for_dispatch',
                ]);
            }
        });
    }

    public function prepareDelivery(
        Order $order,
        array $itemIds,
        ?int $vendorId,
        User $actor,
        array $details,
        ?string $dispatchProofPath = null,
    ): Collection {
        if ($vendorId !== null) {
            abort_unless($actor->hasPermission('orders.view_assigned') && $actor->vendor_id, 403);
            abort_unless((int) $actor->vendor_id === $vendorId, 404);
        } else {
            abort_unless($actor->hasPermission('orders.manage') && $actor->hasPermission('deliveries.manage'), 403);
        }

        return DB::transaction(function () use ($order, $itemIds, $vendorId, $actor, $details, $dispatchProofPath) {
                    $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
                    if (in_array($lockedOrder->fulfillment_status, [
                        'pending_confirmation', 'delivered', 'completed', 'cancelled', 'returned',
                    ], true)) {
                        throw ValidationException::withMessages([
                            'delivery' => 'This order is not eligible for delivery preparation.',
                        ]);
                    }
                    if ($vendorId === null
                        && ($lockedOrder->payment_status !== 'paid'
                            || ! in_array($lockedOrder->fulfillment_status, [
                                'confirmed', 'processing', 'awaiting_stock', 'ready_for_dispatch',
                            ], true))) {
                        throw ValidationException::withMessages([
                            'delivery' => 'The order must be confirmed, paid, and eligible for Admin-managed delivery preparation.',
                        ]);
                    }

                    $items = OrderItem::query()
                        ->where('order_id', $lockedOrder->id)
                        ->whereIn('id', $itemIds)
                        ->when($vendorId !== null, fn ($query) => $query->where('vendor_id', $vendorId))
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();
                    if ($items->isEmpty() || $items->count() !== count(array_unique($itemIds))) {
                        abort(404);
                    }

                    foreach ($items as $item) {
                        $assignmentIsEligible = $vendorId !== null
                            ? $item->assignment_status === 'accepted'
                            : $item->assignment_status === 'admin_handled' && $item->vendor_id === null;
                        $fulfillmentIsEligible = $vendorId !== null
                            ? $item->fulfillment_status === 'ready_for_dispatch'
                            : in_array($item->fulfillment_status, [
                                'confirmed', 'processing', 'awaiting_stock', 'ready_for_dispatch',
                            ], true);
                        if (! $assignmentIsEligible || ! $fulfillmentIsEligible) {
                            throw ValidationException::withMessages([
                                'delivery' => $vendorId !== null
                                    ? 'Every selected vendor item must be prepared and Ready for Delivery.'
                                    : 'Every selected Admin-handled item must remain eligible for delivery preparation.',
                            ]);
                        }
                    }

                    $existingTask = DeliveryTask::query()
                        ->where('order_id', $lockedOrder->id)
                        ->whereIn('order_item_id', $items->modelKeys())
                        ->lockForUpdate()
                        ->exists();
                    if ($existingTask) {
                        throw ValidationException::withMessages([
                            'delivery' => 'A delivery has already been prepared for one or more selected items.',
                        ]);
                    }

                    if ($vendorId === null) {
                        foreach ($items as $item) {
                            if ($item->fulfillment_status === 'ready_for_dispatch') {
                                continue;
                            }

                            $previousStatus = $item->fulfillment_status;
                            $item->fulfillment_status = 'ready_for_dispatch';
                            $item->save();
                            AuditLogger::log($actor, 'order.item_progress', $item, [
                                'from' => $previousStatus,
                                'to' => 'ready_for_dispatch',
                            ]);
                        }
                    }

                    $tasks = $items->map(function (OrderItem $item) use ($lockedOrder, $vendorId, $actor, $details, $dispatchProofPath) {
                        $task = DeliveryTask::query()->create([
                            'reference' => ReferenceGenerator::delivery(),
                            'order_id' => $lockedOrder->id,
                            'order_item_id' => $item->id,
                            'vendor_id' => $vendorId,
                            'product_location_id' => $item->product_location_id,
                            'assigned_officer_id' => $lockedOrder->assigned_officer_id,
                            'delivery_officer_id' => $lockedOrder->delivery_officer_id
                                ?: ($vendorId === null ? $actor->id : null),
                            'status' => 'assigned',
                            'fulfillment_instructions' => $item->fulfillment_instructions,
                            'delivery_method' => $details['delivery_method'] ?? null,
                            'courier_name' => $details['courier_name'] ?? null,
                            'delivery_phone' => $details['delivery_phone'] ?? null,
                            'vehicle_registration' => $details['vehicle_registration'] ?? null,
                            'expected_delivery_at' => $details['expected_delivery_at'] ?? null,
                            'tracking_reference' => $details['tracking_reference'] ?? null,
                            'delivery_notes' => $details['delivery_notes'] ?? null,
                            'dispatch_proof_path' => $dispatchProofPath,
                        ]);

                        AuditLogger::log($actor, 'delivery.prepared', $task, [
                            'order_reference' => $lockedOrder->reference,
                            'order_item_id' => $item->id,
                        ]);

                        return $task;
                    });

            return $tasks;
        });
    }

    public function requestVendorAssignmentDelivery(Order $order, int $vendorId, User $actor): bool
    {
        abort_unless(
            $actor->hasPermission('orders.manage') && $actor->hasPermission('vendors.view'),
            403
        );

        return DB::transaction(function () use ($order, $vendorId) {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (in_array($lockedOrder->fulfillment_status, [
                'pending_confirmation', 'delivered', 'completed', 'cancelled', 'returned',
            ], true)) {
                throw ValidationException::withMessages([
                    'vendor_id' => 'A delivery cannot be requested for this order in its current state.',
                ]);
            }

            $items = OrderItem::query()
                ->where('order_id', $lockedOrder->id)
                ->where('vendor_id', $vendorId)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'vendor_id' => 'This vendor does not have an assignment on the selected order.',
                ]);
            }

            if (DeliveryTask::query()
                ->where('order_id', $lockedOrder->id)
                ->where('vendor_id', $vendorId)
                ->whereIn('order_item_id', $items->modelKeys())
                ->exists()
                || $this->notifier->hasVendorDeliveryRequest($lockedOrder, $vendorId)) {
                return false;
            }

            foreach ($items as $item) {
                if ($item->assignment_status === 'clarification') {
                    throw ValidationException::withMessages([
                        'vendor_id' => 'Resolve the reported fulfillment problem before requesting delivery.',
                    ]);
                }
                if (! in_array($item->assignment_status, ['assigned', 'accepted'], true)
                    || in_array($item->fulfillment_status, ['delivered', 'completed', 'cancelled', 'returned'], true)) {
                    throw ValidationException::withMessages([
                        'vendor_id' => 'This vendor assignment is not eligible for a delivery request.',
                    ]);
                }
            }

            $vendor = Vendor::query()->with('users')->findOrFail($vendorId);
            if ($vendor->users->isEmpty()) {
                throw ValidationException::withMessages([
                    'vendor_id' => 'This vendor has no user account to notify.',
                ]);
            }

            $this->notifier->requestVendorDelivery($lockedOrder, $vendor);

            return true;
        });
    }

    public function assignDeliveryOfficer(Order $order, User $officer, User $actor, ?DeliveryTask $deliveryTask = null): void
    {
        if (! $actor->hasPermission('deliveries.manage') && ! $actor->hasPermission('orders.manage')) {
            abort(403);
        }

        if ($deliveryTask && (int) $deliveryTask->order_id !== (int) $order->id) {
            throw ValidationException::withMessages([
                'delivery' => 'The selected delivery does not belong to this order.',
            ]);
        }

        if ($deliveryTask) {
            $before = $deliveryTask->delivery_officer_id;
            $deliveryTask->delivery_officer_id = $officer->id;
            $deliveryTask->save();

            AuditLogger::log($actor, 'delivery.reassigned', $deliveryTask, ['from' => $before, 'to' => $officer->id]);

            return;
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

    private function syncDelivery(Order $order, User $actor, string $status, string $message, ?DeliveryTask $deliveryTask = null): void
    {
        $task = $deliveryTask ?? $this->ensureDeliveryTask($order, $actor);
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
        if (! $actor->hasPermission('orders.view_assigned')) {
            abort(403);
        }
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

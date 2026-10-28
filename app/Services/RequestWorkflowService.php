<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductRequest;
use App\Models\RequestStatusHistory;
use App\Models\ShopProduct;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Validation\ValidationException;

class RequestWorkflowService
{
    public function __construct(
        private OrderService $orders,
        private MarketplaceNotifier $notifier,
    ) {}

    public function submit(array $data, User|Customer|null $customer, ?string $submissionKey = null): ProductRequest|Order
    {
        if ($submissionKey !== null) {
            $existingOrder = Order::query()->where('submission_key', $submissionKey)->first();

            if ($existingOrder) {
                if ($existingOrder->source !== 'request_product') {
                    throw ValidationException::withMessages([
                        'submission_key' => 'This submission key has already been used.',
                    ]);
                }

                return $existingOrder->load('items');
            }
        }

        $product = null;

        if (! empty($data['product_slug'])) {
            $product = ShopProduct::query()->publicCatalog()->where('slug', $data['product_slug'])->first();

            if (! $product) {
                throw ValidationException::withMessages([
                    'product_slug' => 'Choose a product from the shop, or describe the item you need.',
                ]);
            }
        }

        if ($product && ($data['request_type'] ?? 'purchase') === 'purchase') {
            return $this->orders->place([
                'name' => $data['customer_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'],
                'delivery_address' => trim((string) ($data['delivery_location'] ?? '')) ?: 'To be confirmed',
                'delivery_notes' => $data['instructions'] ?? null,
                'payment_method' => 'cash_on_delivery',
            ], [
                ['product' => $product, 'quantity' => (int) $data['quantity']],
            ], $customer instanceof User ? $customer : null,
                $customer instanceof Customer ? $customer : null,
                'request_product',
                $submissionKey);
        }

        $request = ProductRequest::query()->create([
            'reference' => ReferenceGenerator::request(),
            'user_id' => $customer instanceof User ? $customer->id : null,
            'customer_id' => $customer instanceof Customer ? $customer->id : null,
            'shop_product_id' => $product?->id,
            'customer_name' => $data['customer_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'product_name' => $product?->product_name ?? $data['product_name'],
            'quantity' => $data['quantity'],
            'instructions' => $data['instructions'] ?? null,
            'delivery_location' => $data['delivery_location'] ?? null,
            'priority' => 'normal',
            'status' => 'submitted',
            'customer_message' => 'We have received your request.',
        ]);

        $this->history($request, $customer instanceof User ? $customer : null, null, 'submitted', null, 'We have received your request.', []);
        $this->notifier->requestNeedsStaff($request, 'New product request');

        return $request;
    }

    public function transition(ProductRequest $request, string $to, User $actor, ?string $note = null, ?string $customerMessage = null): void
    {
        if (! $actor->hasPermission('requests.allocate') && ! $actor->hasPermission('requests.reassign')) {
            abort(403);
        }

        $allowed = config('marketplace.request_transitions.'.$request->status, []);

        if (! in_array($to, $allowed, true)) {
            throw ValidationException::withMessages(['status' => 'That request status change is not allowed.']);
        }

        $from = $request->status;
        $request->status = $to;
        if ($customerMessage) {
            $request->customer_message = $customerMessage;
        }
        $request->save();

        $this->history($request, $actor, $from, $to, $note, $customerMessage, []);
        AuditLogger::log($actor, 'request.status_changed', $request, ['from' => $from, 'to' => $to]);
    }

    public function allocate(ProductRequest $request, Vendor $vendor, User $actor, ?int $locationId = null, ?string $note = null): void
    {
        if (! $actor->hasPermission('requests.allocate') && ! $actor->hasPermission('requests.reassign')) {
            abort(403);
        }

        if ($vendor->status !== 'active') {
            throw ValidationException::withMessages(['vendor_id' => 'Choose an active vendor.']);
        }

        $fromVendor = $request->vendor_id;
        $from = $request->status;
        $request->vendor_id = $vendor->id;
        $request->assigned_officer_id = $actor->id;
        $request->product_location_id = $locationId;
        $request->status = 'awaiting_vendor_response';
        $request->customer_message = 'We are arranging your request.';
        $request->save();

        $this->history($request, $actor, $from, 'awaiting_vendor_response', $note, $request->customer_message, [
            'from_vendor' => $fromVendor,
            'to_vendor' => $vendor->id,
            'location_id' => $locationId,
        ]);
        AuditLogger::log($actor, $fromVendor ? 'request.reassigned' : 'request.allocated', $request, [
            'from_vendor' => $fromVendor,
            'to_vendor' => $vendor->id,
        ]);
        $this->notifier->requestAssigned($request);
    }

    public function vendorDecision(ProductRequest $request, User $actor, string $decision, ?string $note = null): void
    {
        if (! $actor->vendor_id || (int) $request->vendor_id !== (int) $actor->vendor_id) {
            abort(404);
        }

        if ($request->status !== 'awaiting_vendor_response') {
            throw ValidationException::withMessages(['decision' => 'This request is not waiting for a vendor response.']);
        }

        if ($decision === 'accept') {
            $from = $request->status;
            $request->status = 'quotation_prepared';
            $request->customer_message = 'We are preparing the next step for your request.';
            $request->save();
            $this->history($request, $actor, $from, $request->status, $note, $request->customer_message, []);
            $this->notifier->requestNeedsStaff($request, 'Vendor accepted a request');

            return;
        }

        if ($decision === 'decline') {
            $previous = $request->vendor_id;
            $from = $request->status;
            $request->vendor_id = null;
            $request->status = 'sourcing';
            $request->customer_message = 'We are still arranging your request.';
            $request->save();
            $this->history($request, $actor, $from, 'sourcing', $note, $request->customer_message, ['declined_vendor' => $previous]);
            AuditLogger::log($actor, 'request.declined', $request, ['vendor_id' => $previous]);
            $this->notifier->requestNeedsStaff($request, 'Request returned for reassignment');

            return;
        }

        $from = $request->status;
        $request->status = 'awaiting_information';
        $request->internal_notes = trim(($request->internal_notes ? $request->internal_notes."\n" : '').($note ?? ''));
        $request->customer_message = 'We need a few more details before we can continue.';
        $request->save();
        $this->history($request, $actor, $from, 'awaiting_information', $note, $request->customer_message, []);
    }

    public function convertToOrder(ProductRequest $request, User $actor): Order
    {
        if (! $actor->hasPermission('orders.manage')) {
            abort(403);
        }

        if ($request->order_id) {
            return $request->order;
        }

        if (! $request->shop_product_id) {
            throw ValidationException::withMessages([
                'order' => 'Link this request to a catalog product before converting it.',
            ]);
        }

        $product = ShopProduct::query()->findOrFail($request->shop_product_id);

        $order = $this->orders->place([
            'name' => $request->customer_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'delivery_address' => $request->delivery_location ?: 'To be confirmed',
            'delivery_notes' => $request->instructions,
            'payment_method' => 'cash_on_delivery',
        ], [
            ['product' => $product, 'quantity' => $request->quantity],
        ], $request->user_id ? User::query()->find($request->user_id) : null,
            $request->customer_id ? Customer::query()->find($request->customer_id) : null,
            'request_product');

        $from = $request->status;
        $request->order_id = $order->id;
        $request->status = 'converted_to_order';
        $request->customer_message = 'Your request is now order '.$order->reference.'.';
        $request->save();
        $this->history($request, $actor, $from, 'converted_to_order', null, $request->customer_message, ['order_id' => $order->id]);

        return $order;
    }

    public function updateInternal(ProductRequest $request, User $actor, array $data): void
    {
        if (! $actor->hasPermission('requests.view_all') && ! $actor->hasPermission('requests.allocate')) {
            abort(403);
        }

        $request->fill([
            'priority' => $data['priority'] ?? $request->priority,
            'assigned_officer_id' => $data['assigned_officer_id'] ?? $request->assigned_officer_id,
            'internal_notes' => $data['internal_notes'] ?? $request->internal_notes,
            'customer_message' => $data['customer_message'] ?? $request->customer_message,
        ]);
        $request->save();
        AuditLogger::log($actor, 'request.updated', $request);
    }

    private function history(ProductRequest $request, ?User $actor, ?string $from, string $to, ?string $note, ?string $customerMessage, array $meta): void
    {
        RequestStatusHistory::query()->create([
            'product_request_id' => $request->id,
            'user_id' => $actor?->id,
            'from_status' => $from,
            'to_status' => $to,
            'note' => $note,
            'customer_message' => $customerMessage,
            'meta' => $meta,
        ]);
    }
}

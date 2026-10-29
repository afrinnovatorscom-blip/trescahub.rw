<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderItem;

class CustomerTracking
{
    public static function label(string $status): string
    {
        return match ($status) {
            'pending_confirmation' => 'Order received',
            'confirmed' => 'Order confirmed',
            'processing' => 'Preparation in progress',
            'awaiting_stock' => 'We are arranging stock for your order',
            'ready_for_dispatch' => 'Ready for Delivery',
            'dispatched' => 'Dispatched',
            'out_for_delivery' => 'Out for delivery',
            'delivered' => 'Delivered',
            'completed' => 'Delivery record created; vendor fulfillment completed',
            'cancellation_requested' => 'Cancellation requested',
            'cancelled' => 'Cancelled',
            'return_requested' => 'Return requested',
            'returned' => 'Returned',
            default => 'Order update',
        };
    }

    public static function timeline(string $status): array
    {
        $steps = [
            'pending_confirmation' => 'Order received',
            'confirmed' => 'Order confirmed',
            'processing' => 'Preparation in progress',
            'ready_for_dispatch' => 'Ready for Delivery',
            'completed' => 'Delivery record created',
            'dispatched' => 'Dispatched',
            'out_for_delivery' => 'Out for delivery',
            'delivered' => 'Delivered',
        ];

        if (in_array($status, ['cancelled', 'cancellation_requested', 'returned', 'return_requested', 'awaiting_stock'], true)) {
            return [
                'mode' => 'exception',
                'headline' => self::label($status),
                'steps' => [
                    ['label' => 'Order received', 'state' => 'complete'],
                    ['label' => self::label($status), 'state' => 'current'],
                ],
            ];
        }

        $keys = array_keys($steps);
        $index = array_search($status, $keys, true);
        $index = $index === false ? 0 : $index;

        $rendered = [];
        foreach ($keys as $position => $key) {
            $state = 'upcoming';
            if ($position < $index) {
                $state = 'complete';
            } elseif ($position === $index) {
                $state = 'current';
            }

            $rendered[] = ['label' => $steps[$key], 'state' => $state];
        }

        return [
            'mode' => 'progress',
            'headline' => self::label($status),
            'steps' => $rendered,
        ];
    }

    public static function present(Order $order): array
    {
        $order->loadMissing('items', 'payments', 'histories', 'deliveries');
        $hasDeliveryConfirmation = $order->histories->contains(
            fn ($history) => $history->to_status === 'delivered'
        ) || ($order->items->isNotEmpty() && $order->items->every(
            fn (OrderItem $item) => $order->deliveries->contains(
                fn ($delivery) => (int) $delivery->order_item_id === (int) $item->id
                    && $delivery->status === 'delivered'
                    && $delivery->delivered_at !== null
            )
        ));
        $trackingStatus = $order->fulfillment_status === 'completed' && $hasDeliveryConfirmation
            ? 'delivered'
            : $order->fulfillment_status;

        $events = $order->histories->map(fn ($history) => [
            'label' => $history->customer_message ?: self::label($history->to_status),
            'occurred_at' => $history->created_at,
        ]);

        $itemIds = $order->items->modelKeys();
        $itemEvents = $itemIds === []
            ? collect()
            : AuditLog::query()
                ->where('auditable_type', (new OrderItem)->getMorphClass())
                ->whereIn('auditable_id', $itemIds)
                ->whereIn('action', ['order.item_accepted', 'order.item_progress'])
                ->orderBy('created_at')
                ->get(['action', 'changes', 'created_at']);

        foreach ($itemEvents as $event) {
            $status = $event->changes['to'] ?? null;
            $label = match (true) {
                $event->action === 'order.item_accepted' => 'Vendor accepted an assigned product',
                $status === 'processing' => 'Preparation in progress for an assigned product',
                $status === 'awaiting_stock' => 'Stock is being arranged for an assigned product',
                $status === 'ready_for_dispatch' => 'A vendor assignment is ready for delivery',
                default => null,
            };

            if ($label) {
                $events->push(['label' => $label, 'occurred_at' => $event->created_at]);
            }
        }

        foreach ($order->deliveries as $delivery) {
            $events->push([
                'label' => 'Delivery record created',
                'occurred_at' => $delivery->created_at,
            ]);

            if ($delivery->dispatched_at) {
                $events->push([
                    'label' => 'Delivery dispatched',
                    'occurred_at' => $delivery->dispatched_at,
                ]);
            }

            if ($delivery->delivered_at) {
                $events->push([
                    'label' => 'Delivery completed',
                    'occurred_at' => $delivery->delivered_at,
                ]);
            }
        }

        return [
            'reference' => $order->reference,
            'customer_name' => $order->customer_name,
            'delivery_address' => $order->delivery_address,
            'placed_at' => $order->created_at,
            'payment_status' => $order->payment_status,
            'fulfillment_status' => $order->fulfillment_status,
            'message' => $order->fulfillment_status === 'completed' && $hasDeliveryConfirmation
                ? self::label('delivered')
                : ($order->customer_status_message ?: self::label($order->fulfillment_status)),
            'estimate' => $order->delivery_estimate_at,
            'total' => $order->total,
            'currency' => 'RWF',
            'timeline' => self::timeline($trackingStatus),
            'items' => $order->items->map(fn ($item) => [
                'name' => $item->product_name,
                'variation_label' => $item->variation_label,
                'variation_value' => $item->variation_value,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
                'fulfillment_status' => $item->fulfillment_status,
                'fulfillment_label' => self::label($item->fulfillment_status),
                'assignment_status' => $item->assignment_status,
                'assignment_label' => self::assignmentLabel($item->assignment_status),
            ])->all(),
            'payments' => $order->payments->map(fn ($payment) => [
                'amount' => $payment->amount,
                'type' => $payment->type,
                'method' => $payment->method,
                'recorded_at' => $payment->created_at,
            ])->all(),
            'deliveries' => $order->deliveries->map(fn ($delivery) => [
                'reference' => $delivery->reference,
                'status' => $delivery->status,
                'status_label' => self::deliveryLabel($delivery->status),
                'message' => $delivery->customer_message,
                'created_at' => $delivery->created_at,
                'dispatched_at' => $delivery->dispatched_at,
                'delivered_at' => $delivery->delivered_at,
            ])->all(),
            'events' => $events
                ->sortBy(fn (array $event) => $event['occurred_at']?->getTimestamp() ?? 0)
                ->values()
                ->all(),
        ];
    }

    public static function assignmentLabel(string $status): string
    {
        return match ($status) {
            'unassigned' => 'Not yet assigned',
            'assigned' => 'Assigned for fulfillment',
            'accepted' => 'Accepted by vendor',
            'clarification' => 'Vendor reported an issue',
            'admin_handled' => 'Handled by Tresca Hub',
            default => 'Assignment status updated',
        };
    }

    public static function deliveryLabel(string $status): string
    {
        return match ($status) {
            'assigned' => 'Delivery created',
            'in_transit' => 'In transit',
            'delivered' => 'Delivered',
            default => 'Delivery status updated',
        };
    }
}

<?php

namespace App\Support;

use App\Models\Order;

class CustomerTracking
{
    public static function label(string $status): string
    {
        return match ($status) {
            'pending_confirmation' => 'Order received',
            'confirmed' => 'Order confirmed',
            'processing' => 'Preparation in progress',
            'awaiting_stock' => 'We are arranging stock for your order',
            'ready_for_dispatch' => 'Ready for dispatch',
            'dispatched' => 'Dispatched',
            'out_for_delivery' => 'Out for delivery',
            'delivered' => 'Delivered',
            'completed' => 'Completed',
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
            'ready_for_dispatch' => 'Ready for dispatch',
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
        $index = $status === 'completed' ? count($keys) - 1 : array_search($status, $keys, true);
        $index = $index === false ? 0 : $index;

        $rendered = [];
        foreach ($keys as $position => $key) {
            $state = 'upcoming';
            if ($position < $index || $status === 'completed') {
                $state = 'complete';
            } elseif ($position === $index) {
                $state = $status === 'completed' ? 'complete' : 'current';
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
        $order->loadMissing('items');

        return [
            'reference' => $order->reference,
            'customer_name' => $order->customer_name,
            'placed_at' => $order->created_at,
            'payment_status' => $order->payment_status,
            'fulfillment_status' => $order->fulfillment_status,
            'message' => $order->customer_status_message ?: self::label($order->fulfillment_status),
            'estimate' => $order->delivery_estimate_at,
            'total' => $order->total,
            'timeline' => self::timeline($order->fulfillment_status),
            'items' => $order->items->map(fn ($item) => [
                'name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
            ])->all(),
        ];
    }
}

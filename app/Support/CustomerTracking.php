<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\DeliveryTask;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Vendor;

class CustomerTracking
{
    private const TRACKING_STAGES = [
        'order_received' => ['status' => 'pending_confirmation', 'label' => 'Order received', 'icon' => 'bi-inbox'],
        'order_confirmed' => ['status' => 'confirmed', 'label' => 'Order confirmed', 'icon' => 'bi-check2-circle'],
        'delivery_created' => ['status' => null, 'label' => 'Delivery record created', 'icon' => 'bi-clipboard-check'],
        'dispatched' => ['status' => 'dispatched', 'label' => 'Dispatched', 'icon' => 'bi-truck'],
        'out_for_delivery' => ['status' => 'out_for_delivery', 'label' => 'Out for delivery', 'icon' => 'bi-geo-alt'],
        'delivered' => ['status' => 'delivered', 'label' => 'Delivered', 'icon' => 'bi-house-check'],
    ];

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

    public static function timeline(string $status, array $events = []): array
    {
        $stageDates = [];
        foreach ($events as $event) {
            $stage = $event['stage'] ?? null;
            $occurredAt = $event['occurred_at'] ?? null;
            if (! isset(self::TRACKING_STAGES[$stage]) || ! $occurredAt) {
                continue;
            }

            if (! isset($stageDates[$stage]) || $occurredAt > $stageDates[$stage]) {
                $stageDates[$stage] = $occurredAt;
            }
        }

        $statusStage = self::stageForStatus($status);
        $latestEvent = collect($events)
            ->filter(fn (array $event) => isset($event['stage'], $event['occurred_at']))
            ->last();
        $currentStage = $latestEvent['stage'] ?? $statusStage;
        $stageOrder = array_flip(array_keys(self::TRACKING_STAGES));
        if ($statusStage && isset($stageDates[$statusStage])
            && (! $latestEvent
                || ($stageOrder[$statusStage] ?? -1) >= ($stageOrder[$latestEvent['stage']] ?? -1))) {
            $currentStage = $statusStage;
        }

        $rendered = [];
        foreach (self::TRACKING_STAGES as $key => $stage) {
            $occurredAt = $stageDates[$key] ?? null;
            $rendered[] = [
                'key' => $key,
                'label' => $stage['label'],
                'icon' => $stage['icon'],
                'state' => $key === $currentStage ? 'current' : ($occurredAt ? 'complete' : 'upcoming'),
                'occurred_at' => $occurredAt,
            ];
        }

        $exceptionStatuses = ['cancelled', 'cancellation_requested', 'returned', 'return_requested', 'awaiting_stock'];
        $exceptionEvent = collect($events)
            ->filter(fn (array $event) => ($event['status'] ?? null) === $status)
            ->sortBy(fn (array $event) => $event['occurred_at'] ?? null)
            ->last();

        return [
            'mode' => in_array($status, $exceptionStatuses, true) ? 'exception' : 'progress',
            'headline' => self::label($status),
            'exception_at' => $exceptionEvent['occurred_at'] ?? null,
            'steps' => $rendered,
        ];
    }

    public static function present(Order $order): array
    {
        $order->loadMissing(
            'items.product',
            'items.vendor',
            'payments',
            'histories',
            'deliveries.officer',
        );
        $allDeliveriesConfirmed = $order->deliveries->isNotEmpty() && $order->deliveries->every(
            fn (DeliveryTask $delivery) => $delivery->status === 'delivered'
                && $delivery->delivered_at !== null
        );
        $hasDeliveryConfirmation = $allDeliveriesConfirmed || $order->histories->contains(
            fn ($history) => $history->to_status === 'delivered'
        ) || ($order->items->isNotEmpty() && $order->items->every(
            fn (OrderItem $item) => $order->deliveries->contains(
                fn ($delivery) => (int) $delivery->order_item_id === (int) $item->id
                    && $delivery->status === 'delivered'
                    && $delivery->delivered_at !== null
            )
        ));
        $hasInTransitDelivery = $order->deliveries->contains(
            fn (DeliveryTask $delivery) => in_array($delivery->status, ['in_transit', 'delivered'], true)
        );
        $trackingStatus = $hasDeliveryConfirmation
            && in_array($order->fulfillment_status, [
                'ready_for_dispatch', 'dispatched', 'out_for_delivery', 'delivered', 'completed',
            ], true)
            ? 'delivered'
            : ($hasInTransitDelivery
                && ! in_array($order->fulfillment_status, ['delivered', 'completed', 'cancelled', 'returned'], true)
                    ? 'out_for_delivery'
                    : $order->fulfillment_status);

        $events = $order->histories->map(fn ($history) => [
            'label' => $history->customer_message ?: self::label($history->to_status),
            'occurred_at' => $history->created_at,
            'stage' => self::stageForStatus($history->to_status),
            'status' => $history->to_status,
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
                $events->push([
                    'label' => $label,
                    'occurred_at' => $event->created_at,
                    'stage' => null,
                    'status' => $status,
                ]);
            }
        }

        foreach ($order->deliveries as $delivery) {
            $events->push([
                'label' => 'Delivery record created',
                'occurred_at' => $delivery->created_at,
                'stage' => 'delivery_created',
                'status' => null,
            ]);

            $dispatchRecordedAt = $delivery->dispatched_at;
            if (in_array($delivery->status, ['in_transit', 'delivered'], true)) {
                $dispatchRecordedAt ??= $delivery->updated_at;
            }

            if ($dispatchRecordedAt) {
                $events->push([
                    'label' => 'Delivery dispatched',
                    'occurred_at' => $dispatchRecordedAt,
                    'stage' => 'dispatched',
                    'status' => 'dispatched',
                ]);
            }

            if (in_array($delivery->status, ['in_transit', 'delivered'], true)) {
                $events->push([
                    'label' => 'Out for delivery',
                    'occurred_at' => $dispatchRecordedAt,
                    'stage' => 'out_for_delivery',
                    'status' => 'out_for_delivery',
                ]);
            }

            if ($delivery->delivered_at) {
                $events->push([
                    'label' => 'Delivery completed',
                    'occurred_at' => $delivery->delivered_at,
                    'stage' => 'delivered',
                    'status' => 'delivered',
                ]);
            }
        }

        $stageOrder = array_flip(array_keys(self::TRACKING_STAGES));
        $events = $events
            ->sort(function (array $left, array $right) use ($stageOrder) {
                $leftAt = $left['occurred_at'];
                $rightAt = $right['occurred_at'];
                if ($leftAt != $rightAt) {
                    return $leftAt <=> $rightAt;
                }

                return ($stageOrder[$left['stage'] ?? ''] ?? -1)
                    <=> ($stageOrder[$right['stage'] ?? ''] ?? -1);
            })
            ->values();
        $timeline = self::timeline($trackingStatus, $events->all());
        $latestEvent = $events->last();
        $refreshToken = sha1(json_encode([
            $order->updated_at?->format('U.u'),
            $order->histories->map(fn ($history) => [$history->id, $history->to_status, $history->created_at?->format('U.u')])->all(),
            $order->items->map(fn ($item) => [$item->id, $item->updated_at?->format('U.u')])->all(),
            $order->payments->map(fn ($payment) => [$payment->id, $payment->created_at?->format('U.u')])->all(),
            $order->deliveries->map(fn ($delivery) => [
                $delivery->id,
                $delivery->status,
                $delivery->updated_at?->format('U.u'),
                $delivery->dispatched_at?->format('U.u'),
                $delivery->delivered_at?->format('U.u'),
            ])->all(),
            $itemEvents->map(fn ($event) => [$event->id, $event->created_at?->format('U.u')])->all(),
        ]));

        return [
            'reference' => $order->reference,
            'customer_name' => $order->customer_name,
            'delivery_address' => $order->delivery_address,
            'placed_at' => $order->created_at,
            'payment_status' => $order->payment_status,
            'fulfillment_status' => $trackingStatus,
            'status_label' => self::label($trackingStatus),
            'message' => $trackingStatus === 'delivered'
                ? self::label('delivered')
                : ($order->customer_status_message ?: self::label($order->fulfillment_status)),
            'estimate' => $order->delivery_estimate_at,
            'total' => $order->total,
            'currency' => 'RWF',
            'timeline' => $timeline,
            'refresh_token' => $refreshToken,
            'last_tracking_update' => $latestEvent ? [
                'label' => $latestEvent['label'],
                'occurred_at' => $latestEvent['occurred_at'],
            ] : null,
            'items' => $order->items->map(fn ($item) => [
                'name' => $item->product_name,
                'variation_label' => $item->variation_label,
                'variation_value' => $item->variation_value,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
                'thumbnail' => $item->product?->thumbnail
                    ? url('assets/'.ltrim(str_replace('public/assets/', '', $item->product->thumbnail), '/'))
                    : null,
                'vendor_name' => self::publicVendorName($item->vendor),
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
                'delivery_method' => $delivery->delivery_method
                    ? (config('marketplace.delivery_methods.'.$delivery->delivery_method) ?? $delivery->delivery_method)
                    : null,
                'courier_name' => $delivery->courier_name,
                'delivery_phone' => $delivery->delivery_phone,
                'tracking_reference' => $delivery->tracking_reference,
                'can_confirm' => self::canConfirmDelivery($order, $delivery),
                'created_at' => $delivery->created_at,
                'updated_at' => $delivery->updated_at,
                'dispatched_at' => $delivery->dispatched_at,
                'delivered_at' => $delivery->delivered_at,
            ])->all(),
            'events' => $events->all(),
        ];
    }

    public static function canConfirmDelivery(Order $order, DeliveryTask $delivery): bool
    {
        return in_array($order->fulfillment_status, [
            'confirmed',
            'processing',
            'awaiting_stock',
            'ready_for_dispatch',
            'dispatched',
            'out_for_delivery',
        ], true) && $delivery->status === 'in_transit';
    }

    private static function stageForStatus(string $status): ?string
    {
        return match ($status) {
            'pending_confirmation' => 'order_received',
            'confirmed' => 'order_confirmed',
            'processing', 'awaiting_stock', 'ready_for_dispatch' => null,
            'dispatched' => 'dispatched',
            'out_for_delivery' => 'out_for_delivery',
            'delivered' => 'delivered',
            default => null,
        };
    }

    private static function publicVendorName(?Vendor $vendor): ?string
    {
        if (! $vendor
            || $vendor->status !== 'active'
            || ! $vendor->show_as_partner
            || $vendor->partner_display_status !== 'published'
            || ! $vendor->public_branding_approved
            || trim((string) $vendor->business_name) === '') {
            return null;
        }

        return $vendor->business_name;
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

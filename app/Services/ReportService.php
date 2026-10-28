<?php

namespace App\Services;

use App\Models\DeliveryTask;
use App\Models\InventoryAdjustmentRequest;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\ProductRequest;
use App\Models\Servicerequest;
use App\Models\ShopProduct;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReportService
{
    /**
     * Build the structured report dataset for the given date range, category filter, and user.
     */
    public function build(Carbon $from, Carbon $to, string $category, User $user): array
    {
        $fromBound = $from->copy()->startOfDay();
        $toBound = $to->copy()->endOfDay();

        $canViewFinance = $user->hasPermission('finance.view');
        $canViewVendors = $user->hasPermission('vendors.view');

        $period = [
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'formatted' => $from->format('d M Y') . ' — ' . $to->format('d M Y'),
            'days' => max(1, (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1),
        ];

        // ── 1. Base Scoped Queries ──
        $ordersQuery = Order::query()->whereBetween('created_at', [$fromBound, $toBound]);
        $orderItemsQuery = OrderItem::query()->whereHas('order', fn ($q) => $q->whereBetween('created_at', [$fromBound, $toBound]));
        $productRequestsQuery = ProductRequest::query()->whereBetween('created_at', [$fromBound, $toBound]);
        $serviceRequestsQuery = Servicerequest::query()->whereBetween('created_at', [$fromBound, $toBound]);
        $inventoryMovementsQuery = InventoryMovement::query()->whereBetween('created_at', [$fromBound, $toBound]);
        $paymentsQuery = OrderPayment::query()->whereBetween('created_at', [$fromBound, $toBound]);
        // Reports must describe completed deliveries from the Deliveries page.
        // Use completion time (rather than creation time) so a delivery completed
        // in this period remains visible even if it was created earlier.
        $deliveryTasksQuery = DeliveryTask::query()
            ->where('status', 'delivered')
            ->whereBetween('delivered_at', [$fromBound, $toBound]);

        // ── 2. Executive Summary Metrics ──
        $ordersTotal = (clone $ordersQuery)->count();
        $ordersActive = (clone $ordersQuery)->where('fulfillment_status', '!=', 'cancelled')->count();
        $ordersCancelled = (clone $ordersQuery)->where('fulfillment_status', 'cancelled')->count();

        $unitsSold = (int) (clone $orderItemsQuery)
            ->whereHas('order', fn ($q) => $q->where('fulfillment_status', '!=', 'cancelled'))
            ->sum('quantity');

        $grossOrderValue = (float) (clone $ordersQuery)
            ->where('fulfillment_status', '!=', 'cancelled')
            ->sum('total');

        // Confirmed revenue: sum of recorded payments in period + orders marked paid without separate payment record
        $collectedRevenue = 0.0;
        if ($canViewFinance) {
            $recordedPayments = (float) (clone $paymentsQuery)->sum('amount');
            $legacyPaidOrders = (float) (clone $ordersQuery)
                ->where('payment_status', 'paid')
                ->doesntHave('payments')
                ->sum('total');
            $collectedRevenue = round($recordedPayments + $legacyPaidOrders, 2);
        }

        $ordersAwaitingConfirmation = (clone $ordersQuery)->where('fulfillment_status', 'pending_confirmation')->count();
        $ordersProcessing = (clone $ordersQuery)->whereIn('fulfillment_status', [
            'confirmed', 'processing', 'awaiting_stock', 'ready_for_dispatch',
        ])->count();
        $ordersDispatched = (clone $ordersQuery)->whereIn('fulfillment_status', ['dispatched', 'out_for_delivery'])->count();
        $ordersDelivered = (clone $ordersQuery)->whereIn('fulfillment_status', ['delivered', 'completed'])->count();
        $ordersCompleted = (clone $ordersQuery)->where('fulfillment_status', 'completed')->count();

        $servicesTotal = (clone $serviceRequestsQuery)->count();
        $servicesPending = (clone $serviceRequestsQuery)->where('status', 'pending')->count();
        $servicesReviewed = (clone $serviceRequestsQuery)->where('status', 'reviewed')->count();
        $servicesCompleted = (clone $serviceRequestsQuery)->where('status', 'completed')->count();

        $trackedProductsCount = ShopProduct::query()->where('tracks_inventory', true)->count();
        $lowStockCount = ShopProduct::query()
            ->where('tracks_inventory', true)
            ->whereRaw('(stock_quantity - reserved_quantity) <= low_stock_threshold')
            ->whereRaw('(stock_quantity - reserved_quantity) > 0')
            ->count();
        $outOfStockCount = ShopProduct::query()
            ->where('tracks_inventory', true)
            ->whereRaw('(stock_quantity - reserved_quantity) <= 0')
            ->count();
        $pendingInventoryRequestsCount = InventoryAdjustmentRequest::query()->where('status', 'pending')->count();

        $summary = [
            'orders_total' => $ordersTotal,
            'orders_active' => $ordersActive,
            'orders_cancelled' => $ordersCancelled,
            'units_sold' => $unitsSold,
            'gross_order_value' => $grossOrderValue,
            'collected_revenue' => $collectedRevenue,
            'orders_awaiting_confirmation' => $ordersAwaitingConfirmation,
            'orders_processing' => $ordersProcessing,
            'orders_dispatched' => $ordersDispatched,
            'orders_delivered' => $ordersDelivered,
            'orders_completed' => $ordersCompleted,
            'services_total' => $servicesTotal,
            'services_pending' => $servicesPending,
            'services_reviewed' => $servicesReviewed,
            'services_completed' => $servicesCompleted,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'pending_inventory_requests' => $pendingInventoryRequestsCount,
        ];

        // ── 3. Product & Sales Performance ──
        $bestSellers = (clone $orderItemsQuery)
            ->whereHas('order', fn ($q) => $q->where('fulfillment_status', '!=', 'cancelled'))
            ->select(
                'shop_product_id',
                'product_name',
                'sku',
                DB::raw('COUNT(DISTINCT order_id) as order_count'),
                DB::raw('SUM(quantity) as units_ordered'),
                DB::raw('SUM(line_total) as gross_revenue'),
                DB::raw("SUM(CASE WHEN fulfillment_status IN ('delivered', 'completed') THEN quantity ELSE 0 END) as units_delivered"),
                DB::raw("SUM(CASE WHEN fulfillment_status IN ('cancelled', 'returned') THEN quantity ELSE 0 END) as units_cancelled")
            )
            ->groupBy('shop_product_id', 'product_name', 'sku')
            ->orderByDesc('units_ordered')
            ->with(['product.category', 'product.vendor'])
            ->get()
            ->map(function ($row) {
                return [
                    'product_id' => $row->shop_product_id,
                    'name' => $row->product_name ?: ($row->product?->product_name ?? 'Custom Product'),
                    'sku' => $row->sku ?: ($row->product?->sku ?? 'N/A'),
                    'category' => $row->product?->category?->name ?? 'General',
                    'vendor' => $row->product?->vendor?->business_name ?? 'In-House / Admin',
                    'order_count' => (int) $row->order_count,
                    'units_ordered' => (int) $row->units_ordered,
                    'units_delivered' => (int) $row->units_delivered,
                    'units_cancelled' => (int) $row->units_cancelled,
                    'gross_revenue' => (float) $row->gross_revenue,
                    'approval_status' => $row->product?->approval_status ?? 'approved',
                    'publication_status' => $row->product?->publication_status ?? 'published',
                ];
            });

        // Order sources comparison
        $orderSources = (clone $ordersQuery)
            ->select(
                'source',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw("SUM(CASE WHEN fulfillment_status != 'cancelled' THEN total ELSE 0 END) as gross_value"),
                DB::raw("SUM(CASE WHEN payment_status = 'paid' THEN total ELSE 0 END) as paid_value"),
                DB::raw("SUM(CASE WHEN fulfillment_status IN ('delivered', 'completed') THEN 1 ELSE 0 END) as delivered_orders"),
                DB::raw("SUM(CASE WHEN fulfillment_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders")
            )
            ->groupBy('source')
            ->get()
            ->map(function ($row) {
                $sourceKey = $row->source ?: 'cart_checkout';
                $label = match ($sourceKey) {
                    'request_product' => 'Product Request (Inquiry)',
                    'cart_checkout', 'checkout' => 'Storefront Cart Checkout',
                    default => Str::headline($sourceKey),
                };

                return [
                    'source' => $sourceKey,
                    'label' => $label,
                    'total_orders' => (int) $row->total_orders,
                    'gross_value' => (float) $row->gross_value,
                    'paid_value' => (float) $row->paid_value,
                    'delivered_orders' => (int) $row->delivered_orders,
                    'cancelled_orders' => (int) $row->cancelled_orders,
                    'delivery_rate' => $row->total_orders > 0 ? round(($row->delivered_orders / $row->total_orders) * 100, 1) : 0,
                ];
            });

        // Product Requests Conversion
        $productRequestsTotal = (clone $productRequestsQuery)->count();
        $productRequestsConverted = (clone $productRequestsQuery)->whereNotNull('order_id')->count();
        $productRequestsConversionRate = $productRequestsTotal > 0
            ? round(($productRequestsConverted / $productRequestsTotal) * 100, 1)
            : 0;

        $productRequestsByStatus = (clone $productRequestsQuery)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $catalogByApproval = ShopProduct::query()
            ->select('approval_status', DB::raw('COUNT(*) as total'))
            ->groupBy('approval_status')
            ->pluck('total', 'approval_status')
            ->toArray();

        $catalogByPublication = ShopProduct::query()
            ->select('publication_status', DB::raw('COUNT(*) as total'))
            ->groupBy('publication_status')
            ->pluck('total', 'publication_status')
            ->toArray();

        $productsData = [
            'best_sellers' => $bestSellers,
            'order_sources' => $orderSources,
            'requests_total' => $productRequestsTotal,
            'requests_converted' => $productRequestsConverted,
            'requests_conversion_rate' => $productRequestsConversionRate,
            'requests_by_status' => $productRequestsByStatus,
            'catalog_by_approval' => $catalogByApproval,
            'catalog_by_publication' => $catalogByPublication,
        ];

        // ── 4. Service Request Performance ──
        $serviceCategories = (clone $serviceRequestsQuery)
            ->select(
                DB::raw("COALESCE(NULLIF(service_category, ''), 'General / Unspecified') as category_name"),
                DB::raw('COUNT(*) as total_requests'),
                DB::raw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count"),
                DB::raw("SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed_count"),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count")
            )
            ->groupBy('category_name')
            ->orderByDesc('total_requests')
            ->get()
            ->map(function ($row) {
                $total = (int) $row->total_requests;
                $completed = (int) $row->completed_count;
                $rate = $total > 0 ? round(($completed / $total) * 100, 1) : 0;

                return [
                    'category' => $row->category_name,
                    'total' => $total,
                    'pending' => (int) $row->pending_count,
                    'reviewed' => (int) $row->reviewed_count,
                    'completed' => $completed,
                    'completion_rate' => $rate,
                ];
            });

        $servicesData = [
            'total' => $servicesTotal,
            'pending' => $servicesPending,
            'reviewed' => $servicesReviewed,
            'completed' => $servicesCompleted,
            'categories' => $serviceCategories,
            'financial_note' => 'Note: Service Requests in Tresca Hub record customer specifications, timelines, and budget expectations. Invoicing and payment for customized services are handled directly with clients, so verified transaction revenue is not stored in the automated checkout ledger.',
        ];

        // ── 5. Orders and Deliveries Report ──
        $ordersByFulfillmentStatus = (clone $ordersQuery)
            ->select('fulfillment_status', DB::raw('COUNT(*) as total'))
            ->groupBy('fulfillment_status')
            ->pluck('total', 'fulfillment_status')
            ->toArray();

        $ordersByPaymentStatus = (clone $ordersQuery)
            ->select('payment_status', DB::raw('COUNT(*) as total'))
            ->groupBy('payment_status')
            ->pluck('total', 'payment_status')
            ->toArray();

        $lateOrdersCount = (clone $ordersQuery)
            ->whereNotNull('delivery_estimate_at')
            ->where('delivery_estimate_at', '<', now())
            ->whereNotIn('fulfillment_status', ['delivered', 'completed', 'cancelled', 'returned'])
            ->count();

        $outstandingOrdersCount = (clone $ordersQuery)
            ->whereIn('fulfillment_status', [
                'pending_confirmation', 'confirmed', 'processing', 'awaiting_stock',
                'ready_for_dispatch', 'dispatched', 'out_for_delivery', 'cancellation_requested',
            ])
            ->count();

        $deliveryTasksTotal = (clone $deliveryTasksQuery)->count();
        $deliveryTasksDelivered = (clone $deliveryTasksQuery)->count();
        $deliveryTasksInTransit = 0;
        $deliverySuccessRate = $deliveryTasksDelivered > 0 ? 100.0 : 0.0;

        // Average fulfillment duration calculation from completed deliveries where timestamps are available
        $completedDeliveryTasks = (clone $deliveryTasksQuery)
            ->whereNotNull('delivered_at')
            ->whereNotNull('created_at')
            ->get(['created_at', 'delivered_at']);

        $averageFulfillmentDays = null;
        if ($completedDeliveryTasks->isNotEmpty()) {
            $totalHours = $completedDeliveryTasks->reduce(function ($carry, $task) {
                return $carry + $task->created_at->diffInHours($task->delivered_at);
            }, 0);
            $averageHours = $totalHours / $completedDeliveryTasks->count();
            $averageFulfillmentDays = round($averageHours / 24, 1);
        }

        // Detailed orders list (up to 30 for the period)
        $detailedOrders = (clone $ordersQuery)
            ->with([
                'items.product',
                'items.vendor',
                'deliveries.officer',
                'payments',
            ])
            ->latest()
            ->limit(30)
            ->get();

        $detailedDeliveries = (clone $deliveryTasksQuery)
            ->with([
                'order',
                'item',
                'vendor',
                'officer',
            ])
            ->latest('delivered_at')
            ->limit(30)
            ->get();

        $ordersData = [
            'by_fulfillment_status' => $ordersByFulfillmentStatus,
            'by_payment_status' => $ordersByPaymentStatus,
            'orders_placed' => $ordersTotal,
            'orders_active' => $ordersActive,
            'orders_outstanding' => $outstandingOrdersCount,
            'orders_cancelled' => $ordersCancelled,
            'late_orders' => $lateOrdersCount,
            'delivery_tasks_total' => $deliveryTasksTotal,
            'delivery_tasks_delivered' => $deliveryTasksDelivered,
            'delivery_tasks_in_transit' => $deliveryTasksInTransit,
            'delivery_success_rate' => $deliverySuccessRate,
            'average_fulfillment_days' => $averageFulfillmentDays,
            'recent_orders' => $detailedOrders,
            'delivery_tasks' => $detailedDeliveries,
        ];

        // ── 6. Inventory & Stock Movement ──
        $movementsTotal = (clone $inventoryMovementsQuery)->count();
        $stockAdded = (int) (clone $inventoryMovementsQuery)
            ->whereIn('type', ['addition', 'initial_stock'])
            ->sum('quantity_delta');

        $stockRemoved = abs((int) (clone $inventoryMovementsQuery)
            ->whereIn('type', ['removal', 'sale'])
            ->sum('quantity_delta'));

        $stockAdjusted = (int) (clone $inventoryMovementsQuery)
            ->where('type', 'adjustment')
            ->sum('quantity_delta');

        $recentMovements = (clone $inventoryMovementsQuery)
            ->with(['product.category', 'actor'])
            ->latest()
            ->limit(30)
            ->get();

        $lowStockProducts = ShopProduct::query()
            ->where('tracks_inventory', true)
            ->whereRaw('(stock_quantity - reserved_quantity) <= low_stock_threshold')
            ->with(['category', 'vendor'])
            ->orderByRaw('(stock_quantity - reserved_quantity) ASC')
            ->limit(20)
            ->get();

        $inventoryData = [
            'tracked_count' => $trackedProductsCount,
            'healthy_count' => max(0, $trackedProductsCount - ($lowStockCount + $outOfStockCount)),
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'movements_total' => $movementsTotal,
            'stock_added' => $stockAdded,
            'stock_removed' => $stockRemoved,
            'stock_adjusted' => $stockAdjusted,
            'pending_adjustment_requests' => $pendingInventoryRequestsCount,
            'low_stock_items' => $lowStockProducts,
            'recent_movements' => $recentMovements,
        ];

        // ── 7. Vendor Performance ──
        $vendorsData = [];
        if ($canViewVendors) {
            // In-house vs vendor items
            $inHouseItems = (clone $orderItemsQuery)
                ->whereHas('order', fn ($q) => $q->where('fulfillment_status', '!=', 'cancelled'))
                ->whereNull('vendor_id');

            $inHouseCount = (clone $inHouseItems)->count();
            $inHouseUnits = (int) (clone $inHouseItems)->sum('quantity');
            $inHouseValue = (float) (clone $inHouseItems)->sum('line_total');
            $inHouseDelivered = (int) (clone $inHouseItems)->whereIn('fulfillment_status', ['delivered', 'completed'])->sum('quantity');

            $vendorAssignedItems = (clone $orderItemsQuery)
                ->whereHas('order', fn ($q) => $q->where('fulfillment_status', '!=', 'cancelled'))
                ->whereNotNull('vendor_id');

            $vendorAssignedCount = (clone $vendorAssignedItems)->count();
            $vendorAssignedUnits = (int) (clone $vendorAssignedItems)->sum('quantity');
            $vendorAssignedValue = (float) (clone $vendorAssignedItems)->sum('line_total');
            $vendorAssignedDelivered = (int) (clone $vendorAssignedItems)->whereIn('fulfillment_status', ['delivered', 'completed'])->sum('quantity');

            $vendorPerformance = Vendor::query()
                ->with(['orderItems' => function ($q) use ($fromBound, $toBound) {
                    $q->whereHas('order', fn ($oq) => $oq->whereBetween('created_at', [$fromBound, $toBound]));
                }])
                ->get()
                ->map(function (Vendor $vendor) {
                    $items = $vendor->orderItems;
                    $assigned = $items->count();
                    $accepted = $items->where('assignment_status', 'accepted')->count();
                    $fulfilled = $items->whereIn('fulfillment_status', ['delivered', 'completed'])->count();
                    $inProgress = $items->whereIn('fulfillment_status', ['processing', 'awaiting_stock', 'ready_for_dispatch'])->count();
                    $totalValue = (float) $items->sum('line_total');

                    return [
                        'vendor_id' => $vendor->id,
                        'name' => $vendor->business_name,
                        'district' => $vendor->district ?: 'N/A',
                        'assigned' => $assigned,
                        'accepted' => $accepted,
                        'in_progress' => $inProgress,
                        'fulfilled' => $fulfilled,
                        'total_value' => $totalValue,
                        'acceptance_rate' => $assigned > 0 ? round(($accepted / $assigned) * 100, 1) : 0,
                        'fulfillment_rate' => $accepted > 0 ? round(($fulfilled / $accepted) * 100, 1) : 0,
                    ];
                })
                ->filter(fn ($row) => $row['assigned'] > 0 || $row['total_value'] > 0)
                ->values();

            $vendorsData = [
                'comparison' => [
                    'in_house' => [
                        'items_count' => $inHouseCount,
                        'units_count' => $inHouseUnits,
                        'gross_value' => $inHouseValue,
                        'delivered_units' => $inHouseDelivered,
                    ],
                    'vendor' => [
                        'items_count' => $vendorAssignedCount,
                        'units_count' => $vendorAssignedUnits,
                        'gross_value' => $vendorAssignedValue,
                        'delivered_units' => $vendorAssignedDelivered,
                    ],
                ],
                'vendors' => $vendorPerformance,
            ];
        }

        return [
            'period' => $period,
            'category' => $category,
            'summary' => $summary,
            'products' => $productsData,
            'services' => $servicesData,
            'orders' => $ordersData,
            'inventory' => $inventoryData,
            'vendors' => $vendorsData,
            'permissions' => [
                'can_view_finance' => $canViewFinance,
                'can_view_vendors' => $canViewVendors,
            ],
        ];
    }
}

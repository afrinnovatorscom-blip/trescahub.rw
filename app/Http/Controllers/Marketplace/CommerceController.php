<?php

namespace App\Http\Controllers\Marketplace;

use App\Exceptions\InventoryException;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DeliveryAttempt;
use App\Models\DeliveryTask;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductRequest;
use App\Models\User;
use App\Models\Vendor;
use App\Services\AuditLogger;
use App\Services\MarketplaceNotifier;
use App\Services\OrderService;
use App\Services\RequestWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CommerceController extends Controller
{
    public function __construct(
        private RequestWorkflowService $requests,
        private OrderService $orders,
        private MarketplaceNotifier $notifier,
    ) {}

    public function requests(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasAnyPermission(['requests.view_all', 'requests.view_assigned']), 403);

        $records = ProductRequest::query()
            ->with(['product', 'vendor', 'officer'])
            ->when(! $user->hasPermission('requests.view_all'), fn ($query) => $query->where('assigned_officer_id', $user->id))
            ->when($request->status, fn ($query) => $query->where('status', $request->status))
            ->when($request->q, fn ($query) => $query->where('reference', 'like', '%'.$request->q.'%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('marketplace.requests.index', ['requests' => $records]);
    }

    public function showRequest(ProductRequest $productRequest)
    {
        $user = auth()->user();
        abort_unless($user->hasPermission('requests.view_all') || ($user->hasPermission('requests.view_assigned') && (int) $productRequest->assigned_officer_id === (int) $user->id), 403);

        $productRequest->load(['product.locations', 'product.vendor', 'vendor', 'officer', 'location', 'histories.actor', 'order']);
        $vendors = $user->hasPermission('vendors.view') ? Vendor::query()->where('status', 'active')->orderBy('business_name')->get() : collect();
        $officers = User::query()->orderBy('name')->limit(100)->get();

        return view('marketplace.requests.show', ['record' => $productRequest, 'vendors' => $vendors, 'officers' => $officers]);
    }

    public function updateRequest(Request $request, ProductRequest $productRequest)
    {
        $data = $request->validate([
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'assigned_officer_id' => ['nullable', 'exists:users,id'],
            'internal_notes' => ['nullable', 'string'],
            'customer_message' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string'],
        ]);

        $this->requests->updateInternal($productRequest, $request->user(), $data);

        if (! empty($data['status']) && $data['status'] !== $productRequest->status) {
            $this->requests->transition($productRequest->refresh(), $data['status'], $request->user(), $data['internal_notes'] ?? null, $data['customer_message'] ?? null);
        }

        return back()->with('success', 'Request updated.');
    }

    public function allocateRequest(Request $request, ProductRequest $productRequest)
    {
        $data = $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'product_location_id' => ['nullable', 'integer'],
            'note' => ['nullable', 'string'],
        ]);

        $vendor = Vendor::query()->findOrFail($data['vendor_id']);
        $permission = $productRequest->vendor_id ? 'requests.reassign' : 'requests.allocate';
        abort_unless($request->user()->hasPermission($permission) || $request->user()->hasPermission('requests.reassign'), 403);

        $this->requests->allocate($productRequest, $vendor, $request->user(), $data['product_location_id'] ?? null, $data['note'] ?? null);

        return back()->with('success', 'Request allocated.');
    }

    public function convertRequest(Request $request, ProductRequest $productRequest)
    {
        try {
            $order = $this->requests->convertToOrder($productRequest, $request->user());
        } catch (InventoryException $exception) {
            throw ValidationException::withMessages(['order' => $exception->getMessage()]);
        }

        return redirect()->route('marketplace.orders.show', $order)->with('success', 'Request converted to '.$order->reference.'.');
    }

    public function orders(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasAnyPermission(['orders.view_all', 'orders.view_assigned']), 403);
        $filters = $request->validate([
            'tab' => ['nullable', 'in:pending,paid,in_delivery,completed,cancelled'],
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:'.implode(',', array_keys(config('marketplace.order_transitions', [])))],
            'payment' => ['nullable', 'in:pending,partially_paid,paid,failed,refunded'],
            'vendor' => ['nullable', 'integer', 'exists:vendors,id'],
            'responsible' => ['nullable', 'in:vendor,admin,unassigned'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
        if (($filters['tab'] ?? null) === 'in_delivery') {
            return ($user->hasPermission('deliveries.manage') || $user->hasPermission('orders.view_assigned'))
                ? redirect()->route('marketplace.deliveries.index')
                : redirect()->route('marketplace.orders.index', ['tab' => 'pending']);
        }
        if (in_array($filters['tab'] ?? null, ['completed', 'cancelled'], true)) {
            return $user->hasPermission('reports.view')
                ? redirect()->route('marketplace.reports.index', ['category' => 'orders'])
                : redirect()->route('marketplace.orders.index', ['tab' => 'pending']);
        }
        $vendorId = $filters['vendor'] ?? null;
        if ($vendorId !== null) {
            abort_unless($user->hasPermission('vendors.view'), 403);
        }

        $vendorAssignmentStatuses = ['assigned', 'accepted', 'clarification'];
        $hasVendorAssignment = static fn ($items) => $items
            ->whereNotNull('vendor_id')
            ->whereIn('assignment_status', $vendorAssignmentStatuses);
        $makeQuery = function () use ($user, $filters, $vendorId, $hasVendorAssignment) {
            return Order::query()
                ->when(! $user->hasPermission('orders.view_all'), function ($query) use ($user) {
                    $query->where(function ($inner) use ($user) {
                        $inner->where('assigned_officer_id', $user->id)
                            ->orWhere('delivery_officer_id', $user->id);
                    });
                })
                ->when(! empty($filters['q']), function ($query) use ($filters) {
                    $search = '%'.$filters['q'].'%';
                    $query->where(function ($inner) use ($search) {
                        $inner->where('reference', 'like', $search)
                            ->orWhere('customer_name', 'like', $search);
                    });
                })
                ->when(! empty($filters['status']), fn ($query) => $query->where('fulfillment_status', $filters['status']))
                ->when(! empty($filters['payment']), fn ($query) => $query->where('payment_status', $filters['payment']))
                ->when(! empty($filters['date_from']), fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
                ->when(! empty($filters['date_to']), fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']))
                ->when($vendorId !== null, fn ($query) => $query->whereHas('items', fn ($items) => $hasVendorAssignment($items)->where('vendor_id', $vendorId)))
                ->when(($filters['responsible'] ?? null) === 'vendor', fn ($query) => $query->whereHas('items', $hasVendorAssignment))
                ->when(($filters['responsible'] ?? null) === 'admin', function ($query) {
                    $query->where(function ($inner) {
                        $inner->whereNotNull('assigned_officer_id')
                            ->orWhereNotNull('delivery_officer_id')
                            ->orWhereHas('items', fn ($items) => $items->where('assignment_status', 'admin_handled'));
                    });
                })
                ->when(($filters['responsible'] ?? null) === 'unassigned', function ($query) {
                    $query->whereNull('assigned_officer_id')
                        ->whereNull('delivery_officer_id')
                        ->whereDoesntHave('items', fn ($items) => $items->whereNotNull('vendor_id')
                            ->orWhere('assignment_status', 'admin_handled'));
                });
        };

        $terminalFulfillmentStatuses = ['completed', 'returned', 'cancelled'];
        $activeFulfillmentStatuses = ['dispatched', 'out_for_delivery', 'delivered'];
        $activeDeliveryStatuses = ['assigned', 'in_transit', 'delivered'];
        $confirmedFulfillmentStatuses = ['confirmed', 'processing', 'awaiting_stock', 'ready_for_dispatch'];
        $unpreparedAdminItems = static fn ($items) => $items
            ->whereNull('vendor_id')
            ->where('assignment_status', 'admin_handled')
            ->whereIn('fulfillment_status', $confirmedFulfillmentStatuses)
            ->whereNotIn('id', DeliveryTask::query()->select('order_item_id')->whereNotNull('order_item_id'));
        $pendingOrders = fn ($query) => $query->where(function ($orders) use ($confirmedFulfillmentStatuses) {
            $orders->whereNotIn('fulfillment_status', $confirmedFulfillmentStatuses)
                ->orWhereHas('items', fn ($items) => $items->whereNull('vendor_id'));
        })
            ->where(function ($orders) {
                $orders->whereIn('fulfillment_status', ['pending_confirmation', 'cancellation_requested', 'return_requested'])
                    ->orWhere(function ($awaitingPayment) {
                        $awaitingPayment->where('payment_status', '!=', 'paid');
                    });
            })
            ->whereNotIn('fulfillment_status', $terminalFulfillmentStatuses)
            ->where(function ($orders) use ($activeFulfillmentStatuses, $activeDeliveryStatuses) {
                $orders->whereNotIn('fulfillment_status', $activeFulfillmentStatuses)
                    ->whereDoesntHave('deliveries', fn ($deliveries) => $deliveries->whereIn('status', $activeDeliveryStatuses))
                    ->orWhereIn('fulfillment_status', ['cancellation_requested', 'return_requested']);
            });
        $paidOrders = fn ($query) => $query
            ->where('payment_status', 'paid')
            ->whereIn('fulfillment_status', $confirmedFulfillmentStatuses)
            ->whereDoesntHave('deliveries', fn ($deliveries) => $deliveries
                ->whereNull('order_item_id')
                ->whereNull('vendor_id'))
            ->whereHas('items', $unpreparedAdminItems);
        $filteredOrders = $makeQuery();
        $tabCounts = [
            'pending' => $pendingOrders(clone $filteredOrders)->count(),
            'paid' => $paidOrders(clone $filteredOrders)->count(),
        ];
        $tab = $filters['tab'] ?? (($filters['payment'] ?? null) === 'paid' ? 'paid' : 'pending');
        $ordersQuery = $makeQuery()
            ->with([
                'items.vendor',
                'officer:id,name',
                'deliveries:id,order_id,order_item_id,vendor_id,reference,status,dispatched_at,delivered_at',
            ])
            ->when($tab === 'pending', $pendingOrders)
            ->when($tab === 'paid', $paidOrders);
        $orders = $ordersQuery->orderByDesc('created_at')->orderByDesc('id')->paginate(20)->withQueryString();
        $vendors = $user->hasPermission('vendors.view') ? Vendor::query()->orderBy('business_name')->get() : collect();
        $statuses = array_keys(config('marketplace.order_transitions', []));

        return view('marketplace.orders.index', compact('orders', 'vendors', 'vendorId', 'filters', 'tab', 'tabCounts', 'statuses'));
    }

    public function showOrder(Request $request, Order $order)
    {
        $user = auth()->user();
        $this->authorizeOrder($user, $order);
        $order->load([
            'items.vendor',
            'items.location',
            'items.product.vendor',
            'histories.actor',
            'payments.recorder',
            'deliveries.attempts',
            'customer',
            'officer',
            'deliveryOfficer',
        ]);
        $assignmentHistory = AuditLog::query()
            ->where('auditable_type', (new OrderItem)->getMorphClass())
            ->whereIn('auditable_id', $order->items->modelKeys())
            ->whereIn('action', [
                'order.item_allocated',
                'order.item_accepted',
                'order.item_declined',
                'order.item_problem_reported',
                'order.item_progress',
            ])
            ->with('actor')
            ->latest()
            ->limit(50)
            ->get();
        $vendors = $user->hasPermission('vendors.view') ? Vendor::query()->where('status', 'active')->orderBy('business_name')->get() : collect();
        $officers = User::query()->orderBy('name')->limit(100)->get();
        $canManageOrders = $user->hasPermission('orders.manage');
        $reviewMode = $order->fulfillment_status === 'pending_confirmation';
        $allItemsHandledByAdmin = $order->items->isNotEmpty()
            && $order->items->every(
                fn (OrderItem $item) => $item->assignment_status === 'admin_handled' && $item->vendor_id === null
            );
        $hasSavedAllocation = $order->items->isNotEmpty()
            && $order->items->every(
                fn (OrderItem $item) => $item->assignment_status === 'admin_handled' && $item->vendor_id === null
            );
        $allocationMode = $reviewMode && $canManageOrders && ! $hasSavedAllocation;
        $acceptMode = $reviewMode && $canManageOrders && $allItemsHandledByAdmin && (
            $request->boolean('accept')
            || $request->session()->has('errors')
            || $hasSavedAllocation
        );
        $amountPaid = (float) $order->payments->sum(fn ($payment) => (float) $payment->amount);
        if ($amountPaid === 0.0 && $order->payment_status === 'paid') {
            $amountPaid = (float) $order->total;
        }
        $outstandingBalance = max(0, round((float) $order->total - $amountPaid, 2));
        $editing = $acceptMode || (! $reviewMode && ($request->boolean('edit') || $request->session()->has('errors')));
        $canAllocate = $user->hasPermission('vendors.view') && (
            $acceptMode || in_array($order->fulfillment_status, [
                'confirmed', 'processing', 'awaiting_stock', 'ready_for_dispatch',
            ], true)
        );
        $deliveryRequestAssignments = $order->items
            ->whereNotNull('vendor_id')
            ->groupBy('vendor_id')
            ->map(function ($items, $vendorId) use ($order) {
                return [
                    'vendor_id' => (int) $vendorId,
                    'vendor_name' => $items->first()->vendor?->business_name ?? 'Vendor',
                    'product_count' => $items->count(),
                    'requested' => $this->notifier->hasVendorDeliveryRequest($order, (int) $vendorId),
                    'has_delivery' => $order->deliveries->contains(
                        fn (DeliveryTask $delivery) => (int) $delivery->vendor_id === (int) $vendorId
                    ),
                ];
            })
            ->values();

        return view('marketplace.orders.show', compact(
            'order',
            'assignmentHistory',
            'vendors',
            'officers',
            'canManageOrders',
            'reviewMode',
            'allocationMode',
            'acceptMode',
            'allItemsHandledByAdmin',
            'hasSavedAllocation',
            'amountPaid',
            'outstandingBalance',
            'editing',
            'canAllocate',
            'deliveryRequestAssignments',
        ));
    }

    public function reviewOrder(Request $request, Order $order)
    {
        abort_unless($request->user()->hasPermission('orders.manage'), 403);
        $data = $request->validate([
            'decision' => ['required', 'in:reject,terminate'],
            'reason' => ['nullable', 'required_if:decision,reject,terminate', 'string', 'max:230'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->orders->reviewOrder(
            $order,
            $request->user(),
            $data['decision'],
            $data['reason'] ?? null,
            $data['note'] ?? null,
        );

        $message = 'Order rejected and the reason recorded.';
        $redirectUrl = route('marketplace.orders.index', ['tab' => 'pending']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'redirect_url' => $redirectUrl,
            ]);
        }

        return redirect()->to($redirectUrl)->with('success', $message);
    }

    public function updateOrder(Request $request, Order $order)
    {
        $data = $request->validate([
            'fulfillment_status' => ['required', 'in:'.implode(',', array_keys(config('marketplace.order_transitions', [])))],
            'note' => ['nullable', 'string'],
            'customer_message' => ['nullable', 'string', 'max:255'],
            'cancellation_reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->orders->transition($order, $data['fulfillment_status'], $request->user(), $data['note'] ?? null, $data['customer_message'] ?? null, $data['cancellation_reason'] ?? null);
        } catch (InventoryException $exception) {
            throw ValidationException::withMessages(['fulfillment_status' => $exception->getMessage()]);
        }

        return back()->with('success', 'Order status updated.');
    }

    public function markVendorAssignmentReady(Request $request, Order $order, Vendor $vendor)
    {
        abort_unless(
            $request->user()->hasPermission('orders.manage')
            && $request->user()->hasPermission('vendors.view'),
            403
        );
        $this->authorizeOrder($request->user(), $order);
        $this->orders->markVendorAssignmentReady($order, (int) $vendor->id, $request->user());

        $message = 'Vendor assignment marked Ready for Delivery.';
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'redirect_url' => route('marketplace.orders.show', $order),
            ]);
        }

        return back()->with('success', $message);
    }

    public function requestVendorDelivery(Request $request, Order $order)
    {
        abort_unless(
            $request->user()->hasPermission('orders.manage')
            && $request->user()->hasPermission('vendors.view'),
            403
        );
        $this->authorizeOrder($request->user(), $order);
        $data = $request->validate([
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
        ]);
        $requested = $this->orders->requestVendorAssignmentDelivery(
            $order,
            (int) $data['vendor_id'],
            $request->user(),
        );
        $message = $requested
            ? 'Delivery request sent to the selected vendor.'
            : 'A delivery request or delivery record already exists for that vendor assignment.';
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'redirect_url' => route('marketplace.orders.show', $order),
            ]);
        }

        return back()->with('success', $message);
    }

    public function saveOrderChanges(Request $request, Order $order)
    {
        abort_unless($request->user()->hasPermission('orders.manage'), 403);
        $data = $request->validate([
            'allocations' => ['nullable', 'array'],
            'allocations.*' => ['nullable', 'string'],
            'allocate_order' => ['nullable', 'in:yes'],
            'order_allocation' => ['required_if:allocate_order,yes', 'nullable', 'string'],
            'accept_order' => ['nullable', 'in:yes'],
            'has_paid' => ['required', 'in:no,yes'],
            'payment_type' => ['required_if:has_paid,yes', 'nullable', 'in:full,partial'],
            'payment_method' => ['required_if:has_paid,yes', 'nullable', 'in:cash_on_delivery,bank_transfer'],
            'partial_amount' => ['nullable', 'numeric', 'gt:0'],
            'submission_key' => ['required', 'uuid'],
        ]);
        if (($data['allocate_order'] ?? null) === 'yes') {
            if (($data['allocations'] ?? []) !== []) {
                throw ValidationException::withMessages([
                    'allocations' => 'Choose one allocation for the entire order.',
                ]);
            }
            $itemIds = $order->items()->pluck('id');
            $data['allocations'] = $itemIds->mapWithKeys(
                fn ($itemId) => [$itemId => $data['order_allocation']]
            )->all();
        }

        $this->orders->saveProcessingChanges(
            $order,
            $request->user(),
            $data['allocations'] ?? [],
            $data['has_paid'] === 'yes',
            $data['payment_type'] ?? null,
            $data['payment_method'] ?? null,
            isset($data['partial_amount']) ? (float) $data['partial_amount'] : null,
            $data['submission_key'],
            ($data['accept_order'] ?? null) === 'yes',
            ($data['allocate_order'] ?? null) === 'yes',
        );

        if (($data['accept_order'] ?? null) === 'yes') {
            $message = 'Order processed successfully and moved to Paid Orders.';
            $redirectUrl = route('marketplace.orders.index', [
                'tab' => $order->fresh()->payment_status === 'paid' ? 'paid' : 'pending',
            ]);
        } elseif (($data['allocate_order'] ?? null) === 'yes') {
            if ($data['order_allocation'] !== 'admin') {
                $vendor = Vendor::query()->whereKey((int) $data['order_allocation'])->where('status', 'active')->firstOrFail();
                $message = 'Order successfully allocated to '.$vendor->business_name.'.';
                $redirectUrl = route('marketplace.orders.index', ['tab' => 'pending']);
            } else {
                $message = 'Order allocated to Admin. Review the saved allocation to accept or reject it.';
                $redirectUrl = route('marketplace.orders.show', $order);
            }
        } else {
            $message = 'Order changes saved.';
            $redirectUrl = route('marketplace.orders.show', $order);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'redirect_url' => $redirectUrl,
            ]);
        }

        return redirect()->to($redirectUrl)->with('success', $message);
    }

    public function assignOrderStaff(Request $request, Order $order)
    {
        abort_unless($request->user()->hasPermission('orders.manage'), 403);
        $data = $request->validate([
            'assigned_officer_id' => ['nullable', 'exists:users,id'],
            'delivery_officer_id' => ['nullable', 'exists:users,id'],
            'internal_notes' => ['nullable', 'string'],
        ]);
        $order->update($data);

        if (! empty($data['delivery_officer_id'])) {
            $this->orders->assignDeliveryOfficer($order, User::query()->findOrFail($data['delivery_officer_id']), $request->user());
        }

        return back()->with('success', 'Assignments saved.');
    }

    public function deliveries(Request $request)
    {
        abort_unless($request->user()->hasPermission('deliveries.manage') || $request->user()->hasPermission('orders.view_assigned'), 403);

        $filters = $request->validate([
            'status' => ['nullable', 'in:assigned,in_transit,delivered'],
            'q' => ['nullable', 'string', 'max:150'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'delivery_method' => ['nullable', 'in:'.implode(',', array_keys(config('marketplace.delivery_methods', [])))],
            'expected_date' => ['nullable', 'date'],
        ]);
        $status = $filters['status'] ?? 'assigned';
        $baseTasks = DeliveryTask::query()
            ->with(['order.items', 'item', 'officer', 'vendor'])
            ->when(
                ! $request->user()->hasPermission('orders.view_all'),
                fn ($query) => $query->where('delivery_officer_id', $request->user()->id)
            )
            ->when($filters['vendor_id'] ?? null, fn ($query, $vendorId) => $query->where('vendor_id', $vendorId))
            ->when($filters['delivery_method'] ?? null, fn ($query, $method) => $query->where('delivery_method', $method))
            ->when($filters['expected_date'] ?? null, fn ($query, $date) => $query->whereDate('expected_delivery_at', $date))
            ->when($filters['q'] ?? null, function ($query, $search) {
                $search = '%'.$search.'%';
                $query->where(function ($deliveries) use ($search) {
                    $deliveries->where('tracking_reference', 'like', $search)
                        ->orWhereHas('order', function ($orders) use ($search) {
                            $orders->where('reference', 'like', $search)
                                ->orWhereHas('items', fn ($items) => $items->where('product_name', 'like', $search));
                        })
                        ->orWhereHas('item', fn ($items) => $items->where('product_name', 'like', $search));
                });
            });
        $tabCounts = [
            'assigned' => (clone $baseTasks)->where('status', 'assigned')->count(),
            'in_transit' => (clone $baseTasks)->where('status', 'in_transit')->count(),
            'delivered' => (clone $baseTasks)->where('status', 'delivered')->count(),
        ];
        $vendors = \App\Models\Vendor::query()
            ->whereIn('id', (clone $baseTasks)->whereNotNull('vendor_id')->select('vendor_id'))
            ->orderBy('business_name')
            ->get(['id', 'business_name']);
        $tasks = $baseTasks
            ->where('status', $status)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $preparationOrders = Order::query()->whereRaw('1 = 0')->paginate(20, ['id', 'reference', 'fulfillment_status', 'created_at'], 'preparation_page');
        if (
            $status === 'assigned'
            && ! ($filters['vendor_id'] ?? null)
            && ! ($filters['delivery_method'] ?? null)
            && ! ($filters['expected_date'] ?? null)
            && $request->user()->hasPermission('orders.manage')
            && $request->user()->hasPermission('deliveries.manage')
        ) {
            $eligibleItems = fn ($items) => $items
                ->whereNull('vendor_id')
                ->where('assignment_status', 'admin_handled')
                ->whereIn('fulfillment_status', ['confirmed', 'processing', 'awaiting_stock', 'ready_for_dispatch'])
                ->whereNotIn('id', DeliveryTask::query()->select('order_item_id')->whereNotNull('order_item_id'));

            $preparationOrders = Order::query()
                ->where('payment_status', 'paid')
                ->whereIn('fulfillment_status', ['confirmed', 'processing', 'awaiting_stock', 'ready_for_dispatch'])
                ->whereHas('items', $eligibleItems)
                ->with(['items' => $eligibleItems])
                ->when($filters['q'] ?? null, function ($orders, $search) {
                    $search = '%'.$search.'%';
                    $orders->where(function ($query) use ($search) {
                        $query->where('reference', 'like', $search)
                            ->orWhereHas('items', fn ($items) => $items
                                ->whereNull('vendor_id')
                                ->where('assignment_status', 'admin_handled')
                                ->whereIn('fulfillment_status', ['confirmed', 'processing', 'awaiting_stock', 'ready_for_dispatch'])
                                ->whereNotIn('id', DeliveryTask::query()->select('order_item_id')->whereNotNull('order_item_id'))
                                ->where('product_name', 'like', $search));
                    });
                })
                ->orderByDesc('created_at')
                ->paginate(20, ['id', 'reference', 'fulfillment_status', 'created_at'], 'preparation_page')
                ->withQueryString();
        }

        return view('marketplace.deliveries.index', compact('tasks', 'tabCounts', 'status', 'filters', 'vendors', 'preparationOrders'));
    }

    public function selectDeliveryOrder(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('orders.manage') && $user->hasPermission('deliveries.manage'), 403);
        $data = $request->validate(['order_id' => ['required', 'integer', 'exists:orders,id']]);

        $order = Order::query()
            ->whereKey($data['order_id'])
            ->where('payment_status', 'paid')
            ->whereIn('fulfillment_status', ['confirmed', 'processing', 'awaiting_stock', 'ready_for_dispatch'])
            ->whereHas('items', fn ($items) => $items
                ->whereNull('vendor_id')
                ->where('assignment_status', 'admin_handled')
                ->whereIn('fulfillment_status', ['confirmed', 'processing', 'awaiting_stock', 'ready_for_dispatch'])
                ->whereNotIn('id', DeliveryTask::query()->select('order_item_id')->whereNotNull('order_item_id')))
            ->firstOrFail();

        return redirect()->route('marketplace.orders.deliveries.prepare', $order);
    }

    public function prepareDelivery(Request $request, Order $order)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('orders.manage') && $user->hasPermission('deliveries.manage'), 403);
        $this->authorizeOrder($user, $order);
        abort_unless(in_array($order->fulfillment_status, [
            'confirmed', 'processing', 'awaiting_stock', 'ready_for_dispatch',
        ], true), 404);
        abort_unless($order->payment_status === 'paid', 404, 'Payment must be recorded before preparing an Admin-managed delivery.');

        $order->load('accountCustomer:id,name,email,phone');
        $items = OrderItem::query()
            ->where('order_id', $order->id)
            ->whereNull('vendor_id')
            ->where('assignment_status', 'admin_handled')
            ->whereIn('fulfillment_status', ['confirmed', 'processing', 'awaiting_stock', 'ready_for_dispatch'])
            ->whereNotIn('id', DeliveryTask::query()->select('order_item_id')->whereNotNull('order_item_id'))
            ->with(['product.category', 'product.subcategory'])
            ->orderBy('id')
            ->get();
        abort_if($items->isEmpty(), 404, 'No eligible administrator-handled items are available for delivery preparation.');

        return view('marketplace.deliveries.prepare', compact('order', 'items'));
    }

    public function prepareExistingDelivery(Request $request, DeliveryTask $deliveryTask)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('orders.manage') && $user->hasPermission('deliveries.manage'), 403);

        $deliveryTask->load([
            'order.accountCustomer:id,name,email,phone',
            'item.product.category',
            'item.product.subcategory',
        ]);
        abort_unless($deliveryTask->order, 404);

        $items = $deliveryTask->item ? collect([$deliveryTask->item]) : collect();

        return view('marketplace.deliveries.prepare', [
            'order' => $deliveryTask->order,
            'items' => $items,
            'deliveryTask' => $deliveryTask,
        ]);
    }

    public function storePreparedDelivery(Request $request, Order $order)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('orders.manage') && $user->hasPermission('deliveries.manage'), 403);
        $this->authorizeOrder($user, $order);
        if ($order->payment_status !== 'paid'
            || ! in_array($order->fulfillment_status, [
                'confirmed', 'processing', 'awaiting_stock', 'ready_for_dispatch',
            ], true)) {
            throw ValidationException::withMessages([
                'delivery' => 'The order must be confirmed, paid, and eligible for Admin-managed delivery preparation.',
            ]);
        }
        $data = $this->deliveryPreparationData($request);
        $items = OrderItem::query()
            ->where('order_id', $order->id)
            ->whereNull('vendor_id')
            ->where('assignment_status', 'admin_handled')
            ->whereIn('fulfillment_status', ['confirmed', 'processing', 'awaiting_stock', 'ready_for_dispatch'])
            ->whereNotIn('id', DeliveryTask::query()->select('order_item_id')->whereNotNull('order_item_id'))
            ->orderBy('id')
            ->get();
        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'delivery' => 'No eligible administrator-handled items are available for delivery preparation.',
            ]);
        }

        $proofPath = $request->file('dispatch_proof')?->store('delivery-dispatch-proofs', 'local');
        $saved = false;
        try {
            $tasks = $this->orders->prepareDelivery(
                $order,
                $items->modelKeys(),
                null,
                $user,
                $data,
                $proofPath,
            );
            $saved = true;
        } finally {
            if (! $saved && $proofPath) {
                Storage::disk('local')->delete($proofPath);
            }
        }

        $message = 'Delivery created and moved to Admin Deliveries. It remains Pending until it is dispatched.';
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'deliveries_url' => route('marketplace.deliveries.index', ['status' => 'assigned']),
                'redirect_url' => route('marketplace.orders.index', ['tab' => 'paid']),
                'delivery_references' => $tasks->pluck('reference')->values(),
            ]);
        }

        return redirect()
            ->route('marketplace.deliveries.index', ['status' => 'assigned'])
            ->with('success', $message);
    }

    public function updatePreparedDelivery(Request $request, DeliveryTask $deliveryTask)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('orders.manage') && $user->hasPermission('deliveries.manage'), 403);
        $deliveryTask->loadMissing('order');
        abort_unless($deliveryTask->order, 404);

        $data = $this->deliveryPreparationData($request, true);
        $newProofPath = $request->file('dispatch_proof')?->store('delivery-dispatch-proofs', 'local');
        $oldProofPath = null;
        $movedToInTransit = false;
        $saved = false;

        try {
            DB::transaction(function () use ($deliveryTask, $data, $newProofPath, $user, &$oldProofPath, &$movedToInTransit) {
                $lockedTask = DeliveryTask::query()->whereKey($deliveryTask->id)->lockForUpdate()->firstOrFail();
                $oldProofPath = $lockedTask->dispatch_proof_path;
                $lockedTask->fill([
                    'courier_name' => $data['courier_name'] ?? null,
                    'delivery_phone' => $data['delivery_phone'] ?? null,
                    'vehicle_registration' => $data['vehicle_registration'] ?? null,
                    'expected_delivery_at' => $data['expected_delivery_at'] ?? null,
                    'tracking_reference' => $data['tracking_reference'] ?? null,
                    'delivery_notes' => $data['delivery_notes'] ?? null,
                ]);
                if ($newProofPath) {
                    $lockedTask->dispatch_proof_path = $newProofPath;
                }
                if ($lockedTask->status === 'assigned') {
                    $lockedTask->status = 'in_transit';
                    $lockedTask->dispatched_at ??= now();
                    $movedToInTransit = true;
                }
                $lockedTask->save();
                AuditLogger::log($user, 'delivery.preparation_updated', $lockedTask);
                if ($movedToInTransit) {
                    AuditLogger::log($user, 'delivery.dispatched', $lockedTask, [
                        'from' => 'assigned',
                        'to' => 'in_transit',
                    ]);
                }
            });
            $saved = true;
        } finally {
            if (! $saved && $newProofPath) {
                Storage::disk('local')->delete($newProofPath);
            }
        }

        if ($newProofPath && $oldProofPath && $oldProofPath !== $newProofPath) {
            Storage::disk('local')->delete($oldProofPath);
        }

        $deliveryTask->refresh();
        $statusFilter = in_array($deliveryTask->status, ['assigned', 'in_transit', 'delivered'], true)
            ? $deliveryTask->status
            : 'assigned';
        $message = $movedToInTransit
            ? 'Delivery information saved and delivery moved to In Transit.'
            : 'Delivery information saved.';

        return redirect()->route('marketplace.deliveries.index', ['status' => $statusFilter])
            ->with('success', $message);
    }

    public function showDelivery(DeliveryTask $deliveryTask)
    {
        $user = auth()->user();
        abort_unless($user->hasPermission('deliveries.manage') || $user->hasPermission('orders.view_assigned'), 403);
        if (! $user->hasPermission('orders.view_all') && (int) $deliveryTask->delivery_officer_id !== (int) $user->id && ! $user->hasPermission('orders.manage')) {
            abort(403);
        }

        $deliveryTask->load(['order', 'item', 'location', 'attempts.actor', 'officer', 'vendor']);
        $officers = User::query()->orderBy('name')->limit(100)->get();
        $deliveryStatusLabel = match ($deliveryTask->status) {
            'assigned' => 'Pending',
            'in_transit' => 'In Transit',
            'delivered' => 'Delivered',
            default => \App\Support\CustomerTracking::deliveryLabel($deliveryTask->status),
        };
        $deliveryStatuses = array_values(array_intersect(
            config('marketplace.order_transitions.'.$deliveryTask->order->fulfillment_status, []),
            ['dispatched', 'out_for_delivery', 'delivered'],
        ));

        return view('marketplace.deliveries.show', [
            'task' => $deliveryTask,
            'officers' => $officers,
            'deliveryStatusLabel' => $deliveryStatusLabel,
            'deliveryStatuses' => $deliveryStatuses,
        ]);
    }

    public function downloadDispatchProof(DeliveryTask $deliveryTask)
    {
        $user = auth()->user();
        abort_unless($user->hasPermission('deliveries.manage'), 403);
        if (! $user->hasPermission('orders.view_all') && ! $user->hasPermission('orders.manage')) {
            abort_unless((int) $deliveryTask->delivery_officer_id === (int) $user->id, 403);
        }
        abort_unless($deliveryTask->dispatch_proof_path, 404);

        return Storage::disk('local')->download($deliveryTask->dispatch_proof_path);
    }

    public function updateDelivery(Request $request, DeliveryTask $deliveryTask)
    {
        abort_unless($request->user()->hasPermission('deliveries.manage'), 403);

        $data = $request->validate([
            'order_status' => ['required', 'in:dispatched,out_for_delivery,delivered'],
            'customer_message' => ['nullable', 'string', 'max:255'],
            'internal_notes' => ['nullable', 'string'],
            'failed_reason' => ['nullable', 'string', 'max:255'],
        ]);
        $deliveryStatuses = array_values(array_intersect(
            config('marketplace.order_transitions.'.$deliveryTask->order->fulfillment_status, []),
            ['dispatched', 'out_for_delivery', 'delivered'],
        ));
        if (! in_array($data['order_status'], $deliveryStatuses, true)) {
            throw ValidationException::withMessages([
                'order_status' => 'This delivery milestone is not available from the current order status.',
            ]);
        }

        try {
            $this->orders->transition(
                $deliveryTask->order,
                $data['order_status'],
                $request->user(),
                $data['internal_notes'] ?? null,
                $data['customer_message'] ?? null,
                deliveryTask: $deliveryTask,
            );
        } catch (InventoryException $exception) {
            throw ValidationException::withMessages(['order_status' => $exception->getMessage()]);
        }

        if ($data['internal_notes'] ?? null) {
            $deliveryTask->internal_notes = $data['internal_notes'];
            $deliveryTask->save();
        }

        return back()->with('success', 'Delivery updated.');
    }

    public function markDeliveryDelivered(Request $request, DeliveryTask $deliveryTask)
    {
        abort_unless($request->user()->hasPermission('deliveries.manage'), 403);
        $this->authorizeOrder($request->user(), $deliveryTask->order);
        $this->orders->markDeliveryDelivered($deliveryTask, $request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Delivery marked as Delivered successfully.',
                'status' => 'delivered',
            ]);
        }

        return back()->with('success', 'Delivery marked as Delivered successfully.');
    }

    public function attempt(Request $request, DeliveryTask $deliveryTask)
    {
        abort_unless($request->user()->hasPermission('deliveries.manage'), 403);
        $data = $request->validate([
            'result' => ['required', 'in:failed,rescheduled'],
            'reason' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        DeliveryAttempt::query()->create([
            'delivery_task_id' => $deliveryTask->id,
            'user_id' => $request->user()->id,
            'attempted_at' => now(),
            'result' => $data['result'],
            'reason' => $data['reason'],
            'note' => $data['note'] ?? null,
        ]);

        $deliveryTask->order->update([
            'customer_status_message' => 'A delivery attempt was unsuccessful. We will try again.',
        ]);
        AuditLogger::log($request->user(), 'delivery.attempted', $deliveryTask, ['result' => $data['result']]);

        return back()->with('success', 'Delivery attempt recorded.');
    }

    public function proof(Request $request, DeliveryTask $deliveryTask)
    {
        abort_unless($request->user()->hasPermission('deliveries.manage'), 403);
        $request->validate(['proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096']]);
        $path = $request->file('proof')->store('delivery-proofs', 'local');
        $deliveryTask->update(['proof_path' => $path]);
        AuditLogger::log($request->user(), 'delivery.proof_uploaded', $deliveryTask);

        return back()->with('success', 'Proof of delivery stored.');
    }

    public function downloadProof(DeliveryTask $deliveryTask)
    {
        abort_unless(auth()->user()->hasPermission('deliveries.manage'), 403);
        abort_unless($deliveryTask->proof_path, 404);

        return Storage::disk('local')->download($deliveryTask->proof_path);
    }

    public function reassignDelivery(Request $request, DeliveryTask $deliveryTask)
    {
        $data = $request->validate(['delivery_officer_id' => ['required', 'exists:users,id']]);
        $this->orders->assignDeliveryOfficer(
            $deliveryTask->order,
            User::query()->findOrFail($data['delivery_officer_id']),
            $request->user(),
            $deliveryTask,
        );

        return back()->with('success', 'Delivery reassigned.');
    }

    private function authorizeOrder(User $user, Order $order): void
    {
        if ($user->hasPermission('orders.view_all') || $user->hasPermission('orders.manage')) {
            return;
        }

        if ($user->hasPermission('orders.view_assigned') && ((int) $order->assigned_officer_id === (int) $user->id || (int) $order->delivery_officer_id === (int) $user->id)) {
            return;
        }

        abort(403);
    }

    private function deliveryPreparationData(Request $request, bool $forDispatch = false): array
    {
        return $request->validate([
            'courier_name' => [$forDispatch ? 'required' : 'nullable', 'string', 'max:150'],
            'delivery_phone' => [$forDispatch ? 'required' : 'nullable', 'string', 'max:40', 'regex:/^[0-9+().\-\s]+$/'],
            'vehicle_registration' => ['nullable', 'string', 'max:40'],
            'expected_delivery_at' => ['nullable', 'date', 'after_or_equal:today'],
            'tracking_reference' => ['nullable', 'string', 'max:100'],
            'delivery_notes' => ['nullable', 'string', 'max:2000'],
            'dispatch_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ]);
    }
}

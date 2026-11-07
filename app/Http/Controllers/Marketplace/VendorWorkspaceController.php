<?php

namespace App\Http\Controllers\Marketplace;

use App\Actions\Fortify\UpdateUserPassword;
use App\Exceptions\InventoryException;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DeliveryTask;
use App\Models\InventoryAdjustmentRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductRequest;
use App\Models\ShopCategory;
use App\Models\ShopProduct;
use App\Services\AuditLogger;
use App\Services\InventoryAdjustmentRequestService;
use App\Services\OrderService;
use App\Services\ProductImageService;
use App\Services\ProductWorkflowService;
use App\Services\RequestWorkflowService;
use App\Support\ProductPayload;
use App\Support\PublicAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class VendorWorkspaceController extends Controller
{
    public function __construct(
        private ProductWorkflowService $products,
        private ProductImageService $productImages,
        private InventoryAdjustmentRequestService $inventoryRequests,
        private RequestWorkflowService $requests,
        private OrderService $orders,
    ) {}

    public function dashboard()
    {
        $vendorId = auth()->user()->vendor_id;
        $ownedProducts = ShopProduct::query()->ownedByVendor($vendorId);
        $confirmedFulfillmentStatuses = ['confirmed', 'processing', 'awaiting_stock', 'ready_for_dispatch'];
        $ordersToFulfill = Order::query()
            ->where('payment_status', 'paid')
            ->whereIn('fulfillment_status', $confirmedFulfillmentStatuses)
            ->whereDoesntHave('deliveries', fn ($deliveries) => $deliveries
                ->whereNull('order_item_id')
                ->where('vendor_id', $vendorId))
            ->whereHas('items', fn ($items) => $items
                ->ownedByVendor($vendorId)
                ->whereIn('assignment_status', ['assigned', 'accepted'])
                ->whereIn('fulfillment_status', $confirmedFulfillmentStatuses)
                ->whereNotIn('id', DeliveryTask::query()
                    ->select('order_item_id')
                    ->whereNotNull('order_item_id')))
            ->count();
        $assignedOrders = Order::query()
            ->whereHas('items', fn ($items) => $items->ownedByVendor($vendorId));
        $rejectedProducts = (clone $ownedProducts)->where('approval_status', 'rejected')->count();
        $requests = ProductRequest::query()->ownedByVendor($vendorId)
            ->where('status', 'awaiting_vendor_response')->count();

        return view('vendor.dashboard', [
            'products' => (clone $ownedProducts)->count(),
            'approved' => (clone $ownedProducts)->where('approval_status', 'approved')->count(),
            'pending' => (clone $ownedProducts)->where(function ($query) {
                $query->where('approval_status', 'pending_approval')
                    ->orWhere('revision_status', 'pending_approval');
            })->count(),
            'rejected' => $rejectedProducts,
            'requests' => $requests,
            'orders' => (clone $assignedOrders)->distinct()->count('orders.id'),
            'ordersToFulfill' => $ordersToFulfill,
            'completedOrders' => (clone $assignedOrders)->where('fulfillment_status', 'completed')->count(),
            'recentOrders' => (clone $assignedOrders)
                ->with(['items' => fn ($items) => $items->ownedByVendor($vendorId)])
                ->latest('orders.created_at')
                ->limit(5)
                ->get(),
            'recentProducts' => (clone $ownedProducts)->latest()->limit(5)->get(),
        ]);
    }

    public function productsIndex()
    {
        abort_unless(auth()->user()->hasPermission('products.view'), 403);
        $products = ShopProduct::query()->ownedByVendor(auth()->user()->vendor_id)->latest()->paginate(15);

        return view('vendor.products.index', compact('products'));
    }

    public function createProduct()
    {
        abort_unless(auth()->user()->hasPermission('products.submit'), 403);

        return view('vendor.products.form', [
            'product' => new ShopProduct,
            'parentCategories' => ShopCategory::with('subcategories')->whereNull('parent_id')->orderBy('name')->get(),
            'vendors' => collect(),
        ]);
    }

    public function storeProduct(Request $request)
    {
        abort_unless($request->user()->hasPermission('products.submit'), 403);
        $validated = $this->payload($request, true);
        [$validated, $newUploads, $obsoleteUploads] = $this->productImages->withUploads($request, $validated, null);

        try {
            $product = $this->products->createVendor($request->user(), $validated);
        } catch (Throwable $exception) {
            foreach ($newUploads as $path) {
                PublicAsset::delete($path);
            }

            throw $exception;
        }

        $this->productImages->deleteUnreferenced($obsoleteUploads);

        return redirect()->route('vendor.products.index')->with('success', 'Product submitted for approval. It is not public yet.');
    }

    public function editProduct(string $slug)
    {
        $product = $this->findProduct($slug);
        $product->load('variations');

        return view('vendor.products.form', [
            'product' => $product,
            'parentCategories' => ShopCategory::with('subcategories')->whereNull('parent_id')->orderBy('name')->get(),
            'vendors' => collect(),
        ]);
    }

    public function updateProduct(Request $request, string $slug)
    {
        $product = $this->findProduct($slug);
        $validated = $this->payload($request, false, $product);
        [$validated, $newUploads, $obsoleteUploads] = $this->productImages->withUploads($request, $validated, $product);
        try {
            $this->products->updateVendor($product, $request->user(), $validated, $request->boolean('submit_for_review'));
        } catch (Throwable $exception) {
            foreach ($newUploads as $path) {
                PublicAsset::delete($path);
            }

            throw $exception;
        }
        $this->productImages->deleteUnreferenced($obsoleteUploads);

        return redirect()->route('vendor.products.index')->with('success', 'Product saved.');
    }

    public function requestsIndex()
    {
        abort_unless(auth()->user()->hasPermission('requests.view_assigned'), 403);
        $requests = ProductRequest::query()->ownedByVendor(auth()->user()->vendor_id)->latest()->paginate(15);

        return view('vendor.requests.index', compact('requests'));
    }

    public function showRequest(string $reference)
    {
        $record = ProductRequest::query()->ownedByVendor(auth()->user()->vendor_id)->where('reference', $reference)->firstOrFail();
        $record->load('product', 'histories');

        return view('vendor.requests.show', compact('record'));
    }

    public function respondRequest(Request $request, string $reference)
    {
        $record = ProductRequest::query()->ownedByVendor(auth()->user()->vendor_id)->where('reference', $reference)->firstOrFail();
        $data = $request->validate([
            'decision' => ['required', 'in:accept,decline,clarification'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $this->requests->vendorDecision($record, $request->user(), $data['decision'], $data['note'] ?? null);

        return back()->with('success', 'Response recorded.');
    }

    public function ordersIndex(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('orders.view_assigned'), 403);
        $filters = $request->validate([
            'tab' => ['nullable', 'in:pending,paid,approved'],
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:'.implode(',', array_keys(config('marketplace.order_transitions', [])))],
            'payment' => ['nullable', 'in:pending,partially_paid,paid,failed,refunded'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
        $vendorId = $user->vendor_id;
        $makeQuery = function () use ($vendorId, $filters) {
            return Order::query()
                ->whereHas('items', fn ($items) => $items->ownedByVendor($vendorId))
                ->when(! empty($filters['q']), function ($query) use ($vendorId, $filters) {
                    $search = '%'.$filters['q'].'%';
                    $query->where(function ($orders) use ($search, $vendorId) {
                        $orders->where('reference', 'like', $search)
                            ->orWhere('customer_name', 'like', $search)
                            ->orWhereHas('items', fn ($items) => $items->ownedByVendor($vendorId)->where('product_name', 'like', $search));
                    });
                })
                ->when(! empty($filters['status']), fn ($query) => $query->where('fulfillment_status', $filters['status']))
                ->when(! empty($filters['payment']), fn ($query) => $query->where('payment_status', $filters['payment']))
                ->when(! empty($filters['date_from']), fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
                ->when(! empty($filters['date_to']), fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']));
        };
        $terminalFulfillmentStatuses = ['completed', 'returned', 'cancelled'];
        $activeFulfillmentStatuses = ['dispatched', 'out_for_delivery', 'delivered'];
        $activeDeliveryStatuses = ['assigned', 'in_transit', 'delivered'];
        $confirmedFulfillmentStatuses = ['confirmed', 'processing', 'awaiting_stock', 'ready_for_dispatch'];
        $vendorItemsWithoutDelivery = static fn ($items) => $items
            ->ownedByVendor($vendorId)
            ->whereIn('assignment_status', ['assigned', 'accepted', 'clarification'])
            ->whereIn('fulfillment_status', $confirmedFulfillmentStatuses)
            ->whereNotIn('id', DeliveryTask::query()->select('order_item_id')->whereNotNull('order_item_id'));
        $pendingOrders = fn ($query) => $query
            ->whereNotIn('fulfillment_status', $terminalFulfillmentStatuses)
            ->whereNull('cancellation_reason')
            ->whereHas('items', fn ($items) => $items
                ->ownedByVendor($vendorId)
                ->whereIn('assignment_status', ['assigned', 'clarification'])
                ->whereNotIn('fulfillment_status', $terminalFulfillmentStatuses))
            ->whereDoesntHave('items', fn ($items) => $items
                ->ownedByVendor($vendorId)
                ->where('assignment_status', 'accepted')
                ->whereNotIn('fulfillment_status', $terminalFulfillmentStatuses))
            ->whereDoesntHave('deliveries', fn ($deliveries) => $deliveries
                ->where(fn ($delivery) => $delivery->where('vendor_id', $vendorId)
                    ->orWhereHas('item', fn ($items) => $items->ownedByVendor($vendorId))));
        $approvedOrders = fn ($query) => $query
            ->whereNotIn('fulfillment_status', $terminalFulfillmentStatuses)
            ->whereNull('cancellation_reason')
            ->whereHas('items', fn ($items) => $items
                ->ownedByVendor($vendorId)
                ->where('assignment_status', 'accepted')
                ->whereNotIn('fulfillment_status', $terminalFulfillmentStatuses))
            ->whereDoesntHave('items', fn ($items) => $items
                ->ownedByVendor($vendorId)
                ->whereIn('assignment_status', ['assigned', 'clarification'])
                ->whereNotIn('fulfillment_status', $terminalFulfillmentStatuses))
            ->whereDoesntHave('deliveries', fn ($deliveries) => $deliveries
                ->where(fn ($delivery) => $delivery->where('vendor_id', $vendorId)
                    ->orWhereHas('item', fn ($items) => $items->ownedByVendor($vendorId))));
        $paidOrders = $approvedOrders;
        $tabCounts = [
            'pending' => $pendingOrders(clone $makeQuery())->count(),
            'approved' => $approvedOrders(clone $makeQuery())->count(),
        ];
        $tab = $filters['tab'] ?? (($filters['payment'] ?? null) === 'paid' ? 'paid' : 'pending');
        $ordersQuery = $makeQuery()
            ->with([
                'items' => fn ($items) => $items->ownedByVendor($vendorId)->with('vendor'),
                'deliveries' => fn ($deliveries) => $deliveries->where(function ($query) use ($vendorId) {
                    $query->whereHas('item', fn ($items) => $items->ownedByVendor($vendorId))
                        ->orWhere(function ($unlinked) use ($vendorId) {
                            $unlinked->whereNull('order_item_id')
                                ->where('vendor_id', $vendorId);
                        });
                }),
            ])
            ->when($tab === 'pending', $pendingOrders)
            ->when($tab === 'paid', $paidOrders)
            ->when($tab === 'approved', $approvedOrders);
        $orders = $ordersQuery->orderByDesc('created_at')->orderByDesc('id')->paginate(20)->withQueryString();
        $statuses = array_keys(config('marketplace.order_transitions', []));

        return view('vendor.orders.index', compact('orders', 'filters', 'tab', 'tabCounts', 'statuses'));
    }

    public function showOrder(string $reference)
    {
        $user = auth()->user();
        abort_unless($user->hasPermission('orders.view_assigned'), 403);
        $vendorId = $user->vendor_id;
        $order = Order::query()
            ->where('reference', $reference)
            ->whereHas('items', fn ($query) => $query->ownedByVendor($vendorId))
            ->with(['deliveries' => fn ($deliveries) => $deliveries->where(function ($query) use ($vendorId) {
                $query->whereHas('item', fn ($items) => $items->ownedByVendor($vendorId))
                    ->orWhere(function ($unlinked) use ($vendorId) {
                        $unlinked->whereNull('order_item_id')
                            ->where('vendor_id', $vendorId);
                    });
            })])
            ->firstOrFail();
        $items = OrderItem::query()
            ->ownedByVendor($vendorId)
            ->where('order_id', $order->id)
            ->with('location')
            ->orderBy('id')
            ->get();
        abort_if($items->isEmpty(), 404);

        $approvedByVendor = $items->isNotEmpty()
            && $items->every(fn ($item) => $item->assignment_status === 'accepted');
        if ($approvedByVendor && ! $order->deliveries()->where(function ($query) use ($vendorId) {
            $query->where('vendor_id', $vendorId)
                ->orWhereHas('item', fn ($items) => $items->ownedByVendor($vendorId));
        })->exists()) {
            // Approved review requests return to the Approved tab instead of remaining actionable.
            return redirect()->route('vendor.orders.index', ['tab' => 'approved']);
        }

        $recordedAmount = (int) round((float) $order->payments()->sum('amount') * 100);
        $outstandingBalance = max(0, (int) round((float) $order->total * 100) - $recordedAmount);
        $assignmentHistory = AuditLog::query()
            ->where('auditable_type', (new OrderItem)->getMorphClass())
            ->whereIn('auditable_id', $items->modelKeys())
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

        $canDecide = $items->isNotEmpty()
            && $items->every(fn ($item) => $item->assignment_status === 'assigned')
            && in_array($order->fulfillment_status, ['pending_confirmation', 'confirmed', 'processing', 'awaiting_stock', 'ready_for_dispatch'], true)
            && is_null($order->cancellation_reason)
            && ! $order->deliveries()->where(function ($query) use ($vendorId) {
                $query->where('vendor_id', $vendorId)
                    ->orWhereHas('item', fn ($items) => $items->ownedByVendor($vendorId));
            })->exists();

        return view('vendor.orders.show', compact('order', 'items', 'assignmentHistory', 'outstandingBalance', 'canDecide'));
    }

    public function markOrderReadyForDelivery(Request $request, string $reference)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('orders.view_assigned'), 403);
        abort_unless($user->vendor_id, 403);

        $order = Order::query()
            ->where('reference', $reference)
            ->whereHas('items', fn ($items) => $items->ownedByVendor($user->vendor_id))
            ->firstOrFail();
        $this->orders->markVendorAssignmentReady($order, (int) $user->vendor_id, $user);

        $message = 'Your assigned products are marked Ready for Delivery.';
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'redirect_url' => route('vendor.orders.show', $order->reference),
            ]);
        }

        return back()->with('success', $message);
    }

    public function createOrderDelivery(Request $request, string $reference)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('orders.view_assigned'), 403);
        abort_unless($user->vendor_id, 403);

        $order = Order::query()
            ->where('reference', $reference)
            ->whereHas('items', fn ($items) => $items->ownedByVendor($user->vendor_id))
            ->firstOrFail();
        abort_if(in_array($order->fulfillment_status, [
            'pending_confirmation', 'delivered', 'completed', 'cancelled', 'returned',
        ], true), 404);
        $items = OrderItem::query()
            ->ownedByVendor($user->vendor_id)
            ->where('order_id', $order->id)
            ->orderBy('id')
            ->get();
        abort_if($items->isEmpty(), 404);

        if (! in_array($order->fulfillment_status, ['confirmed', 'processing', 'awaiting_stock', 'ready_for_dispatch'], true)
            || $items->contains(fn ($item) => $item->assignment_status !== 'accepted')
            || $items->contains(fn ($item) => in_array($item->fulfillment_status, ['delivered', 'completed', 'cancelled', 'returned'], true))) {
            throw ValidationException::withMessages([
                'delivery' => 'This order is not an approved vendor assignment eligible for delivery.',
            ]);
        }

        $data = $this->deliveryPreparationData($request);
        $proofPath = $request->file('dispatch_proof')?->store('delivery-dispatch-proofs', 'local');
        $saved = false;
        try {
            $tasks = $this->orders->prepareDelivery(
                $order,
                $items->modelKeys(),
                (int) $user->vendor_id,
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

        $message = 'Order #'.$order->reference.' was successfully sent to Delivery and is now Pending.';
        $redirectUrl = route('vendor.deliveries.index', ['status' => 'assigned']);
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'redirect_url' => route('vendor.deliveries.index', ['status' => 'assigned']),
                'delivery_references' => $tasks->pluck('reference')->values(),
            ]);
        }

        return redirect()->to($redirectUrl)->with('success', $message);
    }

    public function prepareOrderDelivery(Request $request, string $reference)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('orders.view_assigned') && $user->vendor_id, 403);

        $order = Order::query()
            ->where('reference', $reference)
            ->whereHas('items', fn ($items) => $items->ownedByVendor($user->vendor_id))
            ->firstOrFail();
        abort_unless($order->payment_status === 'paid', 404);
        abort_if(in_array($order->fulfillment_status, [
            'pending_confirmation', 'delivered', 'completed', 'cancelled', 'returned',
        ], true), 404);
        $items = OrderItem::query()
            ->ownedByVendor($user->vendor_id)
            ->where('order_id', $order->id)
            ->with(['product.category', 'product.subcategory'])
            ->orderBy('id')
            ->get();
        abort_if($items->isEmpty(), 404);
        foreach ($items as $item) {
            if ($item->assignment_status !== 'accepted' || $item->fulfillment_status !== 'ready_for_dispatch') {
                abort(404);
            }
        }
        $order->load('accountCustomer:id,name,email,phone');
        abort_if(
            DeliveryTask::query()->whereIn('order_item_id', $items->modelKeys())->exists(),
            404,
            'A delivery has already been prepared for this assignment.'
        );

        return view('vendor.deliveries.prepare', compact('order', 'items'));
    }

    public function prepareExistingDelivery(Request $request, string $reference)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('orders.view_assigned') && $user->vendor_id, 403);

        $task = $this->vendorDeliveryTasks((int) $user->vendor_id)
            ->where('delivery_tasks.vendor_id', $user->vendor_id)
            ->where('delivery_tasks.status', 'assigned')
            ->where('delivery_tasks.reference', $reference)
            ->with([
                'order.accountCustomer:id,name,email,phone',
                'item' => fn ($items) => $items->ownedByVendor($user->vendor_id)->with(['product.category', 'product.subcategory']),
            ])
            ->firstOrFail();
        $items = $task->item
            ? collect([$task->item])
            : OrderItem::query()
                ->ownedByVendor($user->vendor_id)
                ->where('order_id', $task->order_id)
                ->with(['product.category', 'product.subcategory'])
                ->orderBy('id')
                ->get();

        return view('vendor.deliveries.prepare', [
            'order' => $task->order,
            'items' => $items,
            'deliveryTask' => $task,
        ]);
    }

    public function updateExistingDelivery(Request $request, string $reference)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('orders.view_assigned') && $user->vendor_id, 403);
        $data = $this->deliveryPreparationData($request, true);
        $newProofPath = $request->file('dispatch_proof')?->store('delivery-dispatch-proofs', 'local');
        $saved = false;
        $oldProofPath = null;

        try {
            DB::transaction(function () use ($user, $reference, $data, $newProofPath, &$oldProofPath) {
                $task = $this->vendorDeliveryTasks((int) $user->vendor_id)
                    ->where('delivery_tasks.vendor_id', $user->vendor_id)
                    ->where('delivery_tasks.status', 'assigned')
                    ->where('delivery_tasks.reference', $reference)
                    ->lockForUpdate()
                    ->firstOrFail();
                $oldProofPath = $task->dispatch_proof_path;
                $task->fill([
                    'courier_name' => $data['courier_name'],
                    'delivery_phone' => $data['delivery_phone'],
                    'vehicle_registration' => $data['vehicle_registration'] ?? null,
                    'expected_delivery_at' => $data['expected_delivery_at'] ?? null,
                    'tracking_reference' => $data['tracking_reference'] ?? null,
                    'delivery_notes' => $data['delivery_notes'] ?? null,
                ]);
                if ($newProofPath) {
                    $task->dispatch_proof_path = $newProofPath;
                }
                $task->status = 'in_transit';
                $task->dispatched_at ??= now();
                $task->save();
                AuditLogger::log($user, 'delivery.preparation_updated', $task);
                AuditLogger::log($user, 'delivery.dispatched', $task, [
                    'from' => 'assigned',
                    'to' => 'in_transit',
                ]);
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

        return redirect()->route('vendor.deliveries.index', ['status' => 'in_transit'])
            ->with('success', 'Delivery information saved and delivery moved to In Transit.');
    }

    public function markDeliveryDelivered(Request $request, string $reference)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('orders.view_assigned') && $user->vendor_id, 403);
        $task = $this->vendorDeliveryTasks((int) $user->vendor_id)
            ->where('reference', $reference)
            ->firstOrFail();
        $this->orders->markDeliveryDelivered($task, $user);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Delivery marked as Delivered successfully.',
                'status' => 'delivered',
            ]);
        }

        return back()->with('success', 'Delivery marked as Delivered successfully.');
    }

    public function respondOrder(Request $request, OrderItem $item)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:accept,decline,clarification,problem'],
            'note' => ['nullable', 'required_if:decision,problem,clarification', 'string', 'max:2000'],
        ]);
        $this->orders->vendorDecision($item, $request->user(), $data['decision'], $data['note'] ?? null);

        $order = $item->order()->firstOrFail();
        $tab = $order->payment_status === 'paid' ? 'paid' : 'pending';
        $message = $data['decision'] === 'accept'
            ? 'Your assignment was accepted.'
            : 'Response recorded.';
        $redirectUrl = $data['decision'] === 'accept'
            ? route('vendor.orders.index', ['tab' => $tab])
            : route('vendor.orders.show', $order->reference);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'redirect_url' => $redirectUrl,
            ]);
        }

        return redirect()->to($redirectUrl)->with('success', $message);
    }

    public function respondOrderForOrder(Request $request, string $reference)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('orders.view_assigned') && $user->vendor_id, 403);

        $order = Order::query()
            ->where('reference', $reference)
            ->whereHas('items', fn ($items) => $items->ownedByVendor($user->vendor_id))
            ->firstOrFail();

        $data = $request->validate([
            'decision' => ['required', 'in:accept,decline'],
            'note' => ['nullable', 'required_if:decision,decline', 'string', 'max:230'],
            'submission_key' => ['nullable', 'uuid'],
        ]);

        $this->orders->vendorDecisionForOrder(
            $order,
            $user,
            $data['decision'],
            $data['note'] ?? null,
            false,
            null,
            null,
            null,
            $data['submission_key'] ?? null,
        );

        $message = $data['decision'] === 'accept'
            ? 'Order approved successfully and moved to Approved Orders.'
            : 'Order rejected and removed from Pending Orders.';

        if ($data['decision'] === 'accept') {
            $message = 'Order #'.$order->reference.' has been successfully approved.';
        }

        if ($request->expectsJson()) {
            if ($data['decision'] === 'accept') {
                session()->flash('vendor_order_approval_success', $message);
            } else {
                session()->flash('success', $message);
            }
            return response()->json([
                'message' => $message,
                'redirect_url' => $data['decision'] === 'accept'
                    ? route('vendor.orders.index', ['tab' => 'approved'])
                    : route('vendor.orders.index', ['tab' => 'pending']),
            ]);
        }

        $redirect = redirect()->to($data['decision'] === 'accept'
            ? route('vendor.orders.index', ['tab' => 'approved'])
            : route('vendor.orders.index', ['tab' => 'pending']));

        return $data['decision'] === 'accept'
            ? $redirect->with('vendor_order_approval_success', $message)
            : $redirect->with('success', $message);
    }

    public function progressOrder(Request $request, OrderItem $item)
    {
        $data = $request->validate([
            'fulfillment_status' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->orders->vendorProgress($item, $request->user(), $data['fulfillment_status'], $data['note'] ?? null);
        } catch (InventoryException $exception) {
            throw ValidationException::withMessages(['fulfillment_status' => $exception->getMessage()]);
        }

        $message = 'Fulfillment updated.';
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'redirect_url' => route('vendor.orders.show', $item->order()->value('reference')),
            ]);
        }

        return back()->with('success', $message);
    }

    public function deliveriesIndex(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->hasPermission('orders.view_assigned') && $user->vendor_id, 403);

        $vendorId = (int) $user->vendor_id;
        $filters = $request->validate([
            'status' => ['nullable', 'in:assigned,in_transit,delivered'],
            'q' => ['nullable', 'string', 'max:150'],
            'delivery_method' => ['nullable', 'in:'.implode(',', array_keys(config('marketplace.delivery_methods', [])))],
            'expected_date' => ['nullable', 'date'],
        ]);
        $status = $filters['status'] ?? 'assigned';
        $baseTasks = $this->vendorDeliveryTasks($vendorId)
            ->with([
                'order:id,reference,delivery_estimate_at,payment_status',
                'order.items' => fn ($items) => $items->ownedByVendor($vendorId),
                'item' => fn ($items) => $items->ownedByVendor($vendorId),
                'vendor:id,business_name',
            ])
            ->when($filters['delivery_method'] ?? null, fn ($query, $method) => $query->where('delivery_tasks.delivery_method', $method))
            ->when($filters['expected_date'] ?? null, fn ($query, $date) => $query->whereDate('delivery_tasks.expected_delivery_at', $date))
            ->when($filters['q'] ?? null, function ($query, $search) use ($vendorId) {
                $search = '%'.$search.'%';
                $query->where(function ($deliveries) use ($search, $vendorId) {
                    $deliveries->where('delivery_tasks.tracking_reference', 'like', $search)
                        ->orWhereHas('order', function ($orders) use ($search, $vendorId) {
                            $orders->where('reference', 'like', $search)
                                ->orWhereHas('items', fn ($items) => $items
                                    ->ownedByVendor($vendorId)
                                    ->where('product_name', 'like', $search));
                        })
                        ->orWhereHas('item', fn ($items) => $items
                            ->ownedByVendor($vendorId)
                            ->where('product_name', 'like', $search));
                });
            });
        $tabCounts = [
            'assigned' => (clone $baseTasks)->where('delivery_tasks.status', 'assigned')->count(),
            'in_transit' => (clone $baseTasks)->where('delivery_tasks.status', 'in_transit')->count(),
            'delivered' => (clone $baseTasks)->where('delivery_tasks.status', 'delivered')->count(),
        ];
        $tasks = $baseTasks
            ->where('delivery_tasks.status', $status)
            ->latest()
            ->paginate($status === 'delivered' ? 20 : 15)
            ->withQueryString();

        return view('vendor.deliveries.index', compact('tasks', 'tabCounts', 'status', 'filters'));
    }

    public function showDelivery(string $reference)
    {
        abort_unless(auth()->user()->hasPermission('orders.view_assigned'), 403);

        $vendorId = auth()->user()->vendor_id;
        $task = $this->vendorDeliveryTasks($vendorId)
            ->where('reference', $reference)
            ->with([
                'order:id,reference,delivery_estimate_at,payment_status',
                'item:id,order_id,product_name,quantity,vendor_id,fulfillment_instructions',
                'vendor:id,business_name',
            ])
            ->firstOrFail();
        $items = $task->item
            ? collect([$task->item])
            : OrderItem::query()
                ->ownedByVendor($vendorId)
                ->where('order_id', $task->order_id)
                ->orderBy('id')
                ->get(['id', 'product_name', 'quantity', 'fulfillment_instructions']);

        return view('vendor.deliveries.show', compact('task', 'items'));
    }

    public function downloadDispatchProof(string $reference)
    {
        $user = auth()->user();
        abort_unless($user->hasPermission('orders.view_assigned') && $user->vendor_id, 403);
        $task = $this->vendorDeliveryTasks((int) $user->vendor_id)
            ->where('reference', $reference)
            ->firstOrFail();
        abort_unless($task->dispatch_proof_path, 404);

        return Storage::disk('local')->download($task->dispatch_proof_path);
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

    public function inventory()
    {
        abort_unless(auth()->user()->hasPermission('inventory.view'), 403);
        $products = ShopProduct::query()->ownedByVendor(auth()->user()->vendor_id)->with('locations')->where('tracks_inventory', true)->orderBy('product_name')->get();
        $requests = InventoryAdjustmentRequest::query()
            ->with(['product', 'reviewer'])
            ->where('vendor_id', auth()->user()->vendor_id)
            ->latest()
            ->paginate(15);

        return view('vendor.inventory', compact('products', 'requests'));
    }

    private function vendorDeliveryTasks(?int $vendorId)
    {
        return DeliveryTask::query()
            ->where(function ($query) use ($vendorId) {
                $query->whereHas('item', fn ($itemQuery) => $itemQuery->ownedByVendor($vendorId))
                    ->orWhere(function ($taskQuery) use ($vendorId) {
                        $taskQuery->whereNull('order_item_id')
                            ->where('vendor_id', $vendorId);
                    });
            });
    }

    public function adjust(Request $request, string $slug)
    {
        abort_unless($request->user()->hasPermission('inventory.manage'), 403);
        $product = $this->findProduct($slug);
        $data = $request->validate([
            'change_type' => ['required', 'in:addition,reduction,correction'],
            'quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $this->inventoryRequests->submit($request->user(), $product, $data['change_type'], (int) $data['quantity'], $data['reason']);

        return back()->with('success', 'Inventory change submitted for administrator approval. Approved stock has not changed.');
    }

    public function profile()
    {
        $user = auth()->user();
        abort_unless($user->hasRole('vendor'), 403);
        $vendor = $user->vendor;
        abort_unless($vendor, 403);

        return view('vendor.profile', compact('vendor', 'user'));
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasRole('vendor'), 403);
        $vendor = $user->vendor;
        abort_unless($vendor, 403);

        $linkedAccount = $vendor->users()
            ->where('email', $vendor->email)
            ->orderBy('id')
            ->first();
        $userEmailRule = Rule::unique('users', 'email');
        if ($linkedAccount) {
            $userEmailRule->ignore($linkedAccount->id);
        }

        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('vendors', 'email')->ignore($vendor->id), $userEmailRule],
            'phone' => ['nullable', 'string', 'max:50'],
            'district' => ['nullable', 'string', 'max:255'],
            'operating_location' => ['nullable', 'string', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:2048'],
            'address_notes' => ['nullable', 'string', 'max:5000'],
            'partner_description' => ['nullable', 'string', 'max:500'],
            'business_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        $oldLogoPath = $vendor->business_logo_path;
        $newLogoPath = $request->hasFile('business_logo')
            ? PublicAsset::store($request->file('business_logo'), 'vendors/logos')
            : null;

        try {
            DB::transaction(function () use ($vendor, $data, $newLogoPath, $request, $user) {
                $vendor->update([
                    'business_name' => $data['business_name'],
                    'legal_name' => $data['legal_name'] ?? null,
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'district' => $data['district'] ?? null,
                    'operating_location' => $data['operating_location'] ?? null,
                    'website_url' => $data['website_url'] ?? null,
                    'address_notes' => $data['address_notes'] ?? null,
                    'partner_description' => $data['partner_description'] ?? null,
                    'business_logo_path' => $newLogoPath ?? ($request->boolean('remove_logo') ? null : $vendor->business_logo_path),
                ]);

                AuditLogger::log($user, 'vendor.profile_updated', $vendor, [
                    'fields' => array_values(array_intersect(
                        array_keys($data),
                        ['business_name', 'legal_name', 'email', 'phone', 'district', 'operating_location', 'website_url', 'address_notes', 'partner_description']
                    )),
                    'logo_updated' => (bool) ($newLogoPath || $request->boolean('remove_logo')),
                ]);
            });
        } catch (Throwable $exception) {
            PublicAsset::delete($newLogoPath);
            throw $exception;
        }

        if ($newLogoPath || $request->boolean('remove_logo')) {
            PublicAsset::delete($oldLogoPath);
        }

        return back()->with('success', 'Business profile saved.');
    }

    public function updatePassword(Request $request, UpdateUserPassword $passwordUpdater)
    {
        abort_unless($request->user()->hasRole('vendor') && $request->user()->vendor_id, 403);
        $passwordUpdater->update($request->user(), $request->only([
            'current_password',
            'password',
            'password_confirmation',
        ]));
        $request->session()->regenerate();

        return back()->with('success', 'Your password has been updated.');
    }

    private function findProduct(string $slug): ShopProduct
    {
        abort_unless(auth()->user()->hasPermission('products.edit') || auth()->user()->hasPermission('products.view'), 403);

        return ShopProduct::query()->ownedByVendor(auth()->user()->vendor_id)->where('slug', $slug)->firstOrFail();
    }

    private function payload(Request $request, bool $creating = false, ?ShopProduct $product = null): array
    {
        $validated = ProductPayload::normalize(ProductPayload::validate(
            $request,
            $creating,
            $product,
        ));
        unset($validated['vendor_id'], $validated['acquisition_cost'], $validated['internal_notes'], $validated['publish_now'], $validated['submit_for_review']);

        return $validated;
    }
}

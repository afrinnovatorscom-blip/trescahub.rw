<?php

namespace App\Http\Controllers\Marketplace;

use App\Exceptions\InventoryException;
use App\Http\Controllers\Controller;
use App\Models\DeliveryAttempt;
use App\Models\DeliveryTask;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductRequest;
use App\Models\User;
use App\Models\Vendor;
use App\Services\AuditLogger;
use App\Services\OrderService;
use App\Services\RequestWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CommerceController extends Controller
{
    public function __construct(
        private RequestWorkflowService $requests,
        private OrderService $orders,
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

        $orders = Order::query()
            ->with('items')
            ->when(! $user->hasPermission('orders.view_all'), function ($query) use ($user) {
                $query->where(function ($inner) use ($user) {
                    $inner->where('assigned_officer_id', $user->id)
                        ->orWhere('delivery_officer_id', $user->id);
                });
            })
            ->when($request->status, fn ($query) => $query->where('fulfillment_status', $request->status))
            ->when($request->payment, fn ($query) => $query->where('payment_status', $request->payment))
            ->when($request->q, fn ($query) => $query->where('reference', 'like', '%'.$request->q.'%'))
            ->when($request->vendor && $user->hasPermission('vendors.view'), function ($query) use ($request) {
                $query->whereHas('items', fn ($items) => $items->where('vendor_id', $request->vendor));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $vendors = $user->hasPermission('vendors.view') ? Vendor::query()->orderBy('business_name')->get() : collect();

        return view('marketplace.orders.index', compact('orders', 'vendors'));
    }

    public function showOrder(Order $order)
    {
        $user = auth()->user();
        $this->authorizeOrder($user, $order);
        $order->load(['items.vendor', 'items.location', 'histories.actor', 'deliveries.attempts', 'officer', 'deliveryOfficer']);
        $vendors = $user->hasPermission('vendors.view') ? Vendor::query()->where('status', 'active')->orderBy('business_name')->get() : collect();
        $officers = User::query()->orderBy('name')->limit(100)->get();

        return view('marketplace.orders.show', compact('order', 'vendors', 'officers'));
    }

    public function updateOrder(Request $request, Order $order)
    {
        $data = $request->validate([
            'fulfillment_status' => ['required', 'string'],
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

    public function updatePayment(Request $request, Order $order)
    {
        $data = $request->validate(['payment_status' => ['required', 'in:pending,paid,failed,refunded']]);
        $this->orders->updatePayment($order, $data['payment_status'], $request->user());

        return back()->with('success', 'Payment status updated. Fulfillment was left unchanged.');
    }

    public function allocateItem(Request $request, OrderItem $item)
    {
        $data = $request->validate(['vendor_id' => ['required', 'exists:vendors,id']]);
        $this->orders->allocateItem($item, Vendor::query()->findOrFail($data['vendor_id']), $request->user());

        return back()->with('success', 'Order item allocated.');
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

        $tasks = DeliveryTask::query()
            ->with(['order', 'officer'])
            ->when(! $request->user()->hasPermission('orders.view_all'), fn ($query) => $query->where('delivery_officer_id', $request->user()->id))
            ->when($request->status, fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate(20);

        return view('marketplace.deliveries.index', compact('tasks'));
    }

    public function showDelivery(DeliveryTask $deliveryTask)
    {
        $user = auth()->user();
        abort_unless($user->hasPermission('deliveries.manage') || $user->hasPermission('orders.view_assigned'), 403);
        if (! $user->hasPermission('orders.view_all') && (int) $deliveryTask->delivery_officer_id !== (int) $user->id && ! $user->hasPermission('orders.manage')) {
            abort(403);
        }

        $deliveryTask->load(['order.items', 'location', 'attempts.actor', 'officer']);
        $officers = User::query()->orderBy('name')->limit(100)->get();

        return view('marketplace.deliveries.show', ['task' => $deliveryTask, 'officers' => $officers]);
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

        if ($data['internal_notes'] ?? null) {
            $deliveryTask->internal_notes = $data['internal_notes'];
            $deliveryTask->save();
        }

        try {
            $this->orders->transition($deliveryTask->order, $data['order_status'], $request->user(), $data['internal_notes'] ?? null, $data['customer_message'] ?? null);
        } catch (InventoryException $exception) {
            throw ValidationException::withMessages(['order_status' => $exception->getMessage()]);
        }

        return back()->with('success', 'Delivery updated.');
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
        $this->orders->assignDeliveryOfficer($deliveryTask->order, User::query()->findOrFail($data['delivery_officer_id']), $request->user());

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
}

@php
    $order = $task->order;
    $items = $order?->items ?? collect();
    $approval = $order?->histories?->first(fn ($history) => in_array($history->to_status, ['confirmed', 'approved'], true));
    $prepared = $deliveryAudits->first(fn ($audit) => $audit->action === 'delivery.prepared');
    $dispatched = $deliveryAudits->first(fn ($audit) => in_array($audit->action, ['delivery.dispatched', 'delivery.preparation_updated'], true));
    $completed = $deliveryAudits->first(fn ($audit) => $audit->action === 'delivery.completed');
    $received = $customerConfirmation?->created_at;

    $formatDuration = function ($start, $end) {
        if (! $start || ! $end || $end->lt($start)) return 'Not recorded';
        $minutes = $start->diffInMinutes($end);
        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);
        $remaining = $minutes % 60;
        $parts = [];
        if ($days) $parts[] = $days . ' day' . ($days === 1 ? '' : 's');
        if ($hours) $parts[] = $hours . ' hour' . ($hours === 1 ? '' : 's');
        if (! $days && ! $hours && $remaining) $parts[] = $remaining . ' minute' . ($remaining === 1 ? '' : 's');
        return $parts ? implode(' ', $parts) : 'Less than 1 minute';
    };
@endphp

<div class="delivery-completed-record">
    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
            <a href="{{ route('marketplace.deliveries.index', ['status' => 'delivered']) }}" class="delivery-back-link">← Completed deliveries</a>
            <h1 class="h3 mb-1 mt-2">Delivery record</h1>
            <div class="text-muted small">{{ $task->reference }}</div>
        </div>
        <span class="delivery-status-delivered">Delivered</span>
    </div>

    <section class="delivery-card mb-3">
        <div class="delivery-card-heading"><h2>Delivery overview</h2></div>
        <div class="delivery-info-grid">
            <div><span>Delivery reference</span><strong>{{ $task->reference }}</strong></div>
            <div><span>Related order</span><strong>{{ $order?->reference ?: 'Not recorded' }}</strong></div>
            <div><span>Order date</span><strong>{{ $order?->created_at?->format('M j, Y g:i A') ?: 'Not recorded' }}</strong></div>
            <div><span>Completed</span><strong>{{ $task->delivered_at?->format('M j, Y g:i A') ?: 'Not recorded' }}</strong></div>
            <div><span>Total duration</span><strong>{{ $formatDuration($order?->created_at, $task->delivered_at) }}</strong></div>
            <div><span>Customer receipt</span><strong>{{ $received ? 'Confirmed by customer' : 'Not confirmed' }}</strong></div>
            <div><span>Delivery attempts</span><strong>{{ $task->attempts->count() + ($task->status === 'delivered' ? 1 : 0) }}</strong></div>
            <div><span>Timing</span><strong>{{ $task->expected_delivery_at && $task->delivered_at ? ($task->delivered_at->startOfDay()->lte($task->expected_delivery_at->endOfDay()) ? 'On time' : 'Late') : 'Not recorded' }}</strong></div>
        </div>
    </section>

    <section class="delivery-card mb-3">
        <div class="delivery-card-heading"><h2>Order and customer information</h2></div>
        <div class="table-responsive">
            <table class="table delivery-products mb-0">
                <thead><tr><th>Product</th><th>Category / SKU</th><th class="text-center">Quantity</th><th class="text-end">Unit price</th><th class="text-end">Line total</th></tr></thead>
                <tbody>
                @forelse($items as $item)
                    @php $product = $item->product; @endphp
                    <tr>
                        <td><strong>{{ $item->product_name }}</strong>@if($item->variation_value)<br><span class="text-muted small">{{ $item->variation_label }}: {{ $item->variation_value }}</span>@endif</td>
                        <td>{{ $product?->category?->name ?: 'Not recorded' }}<br><span class="text-muted small">SKU: {{ $item->sku ?: $product?->sku ?: 'Not recorded' }}</span></td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-end">RWF {{ number_format((float) $item->unit_price, 0, '.', ',') }}</td>
                        <td class="text-end"><strong>RWF {{ number_format((float) $item->line_total, 0, '.', ',') }}</strong></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">No order products recorded.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="delivery-order-meta">
            <div><span>Customer</span><strong>{{ $order?->customer_name ?: $order?->customer?->name ?: 'Not recorded' }}</strong></div>
            <div><span>Phone</span><strong>{{ $order?->phone ?: $order?->customer?->phone ?: 'Not recorded' }}</strong></div>
            <div><span>Delivery address</span><strong>{{ $order?->delivery_address ?: 'Not recorded' }}</strong></div>
            <div><span>Delivery instructions</span><strong>{{ $order?->delivery_notes ?: 'Not recorded' }}</strong></div>
        </div>
    </section>

    <div class="delivery-record-grid mb-3">
        <section class="delivery-card">
            <div class="delivery-card-heading"><h2>Delivery timeline</h2></div>
            <ol class="completed-timeline">
                <li><strong>Order placed</strong><time>{{ $order?->created_at?->format('M j, Y g:i A') ?: 'Not recorded' }}</time></li>
                <li><strong>Order approved</strong><time>{{ $approval?->created_at?->format('M j, Y g:i A') ?: 'Not recorded' }}</time></li>
                <li><strong>Assigned to vendor or administrator</strong><time>{{ $prepared?->created_at?->format('M j, Y g:i A') ?: 'Not recorded' }}</time></li>
                <li><strong>Sent to Deliveries</strong><time>{{ $prepared?->created_at?->format('M j, Y g:i A') ?: 'Not recorded' }}</time></li>
                <li><strong>Courier assigned</strong><time>{{ $prepared?->created_at?->format('M j, Y g:i A') ?: 'Not recorded' }}</time></li>
                <li><strong>Product picked up</strong><time>Not recorded</time></li>
                <li><strong>Out for delivery</strong><time>{{ $dispatched?->created_at?->format('M j, Y g:i A') ?: $task->dispatched_at?->format('M j, Y g:i A') ?: 'Not recorded' }}</time></li>
                <li><strong>Delivery completed</strong><time>{{ $task->delivered_at?->format('M j, Y g:i A') ?: 'Not recorded' }}</time></li>
                <li><strong>Customer receipt confirmed</strong><time>{{ $received?->format('M j, Y g:i A') ?: 'Not recorded' }}</time></li>
            </ol>
        </section>

        <section class="delivery-card">
            <div class="delivery-card-heading"><h2>Delivery performance</h2></div>
            <dl class="delivery-detail-list">
                <div><dt>Order to delivery</dt><dd>{{ $formatDuration($order?->created_at, $task->delivered_at) }}</dd></div>
                <div><dt>Approval to delivery</dt><dd>{{ $formatDuration($approval?->created_at, $task->delivered_at) }}</dd></div>
                <div><dt>Pickup to delivery</dt><dd>{{ $formatDuration($dispatched?->created_at ?: $task->dispatched_at, $task->delivered_at) }}</dd></div>
                <div><dt>Expected delivery</dt><dd>{{ $task->expected_delivery_at?->format('M j, Y') ?: 'Not recorded' }}</dd></div>
                <div><dt>On-time result</dt><dd>{{ $task->expected_delivery_at && $task->delivered_at ? ($task->delivered_at->startOfDay()->lte($task->expected_delivery_at->endOfDay()) ? 'On time' : 'Late') : 'Not recorded' }}</dd></div>
                <div><dt>Completion to receipt</dt><dd>{{ $formatDuration($task->delivered_at, $received) }}</dd></div>
                <div><dt>Successful / unsuccessful attempts</dt><dd>{{ $task->status === 'delivered' ? 1 : 0 }} / {{ $task->attempts->count() }}</dd></div>
            </dl>
        </section>
    </div>

    <div class="delivery-record-grid mb-3">
        <section class="delivery-card">
            <div class="delivery-card-heading"><h2>Courier and assignment</h2></div>
            <dl class="delivery-detail-list">
                <div><dt>Courier / company</dt><dd>{{ $task->courier_name ?: 'Not recorded' }}</dd></div>
                <div><dt>Courier phone</dt><dd>{{ $task->delivery_phone ?: 'Not recorded' }}</dd></div>
                <div><dt>Vehicle registration</dt><dd>{{ $task->vehicle_registration ?: 'Not recorded' }}</dd></div>
                <div><dt>Delivery method</dt><dd>{{ $task->delivery_method ?: 'Not recorded' }}</dd></div>
                <div><dt>Assigned officer</dt><dd>{{ $task->officer?->name ?: $task->assignedOfficer?->name ?: 'Not assigned' }}</dd></div>
                <div><dt>Assigned vendor</dt><dd>{{ $task->vendor?->business_name ?: 'Admin handled / not recorded' }}</dd></div>
                <div><dt>Assignment date</dt><dd>{{ $prepared?->created_at?->format('M j, Y g:i A') ?: $task->created_at?->format('M j, Y g:i A') ?: 'Not recorded' }}</dd></div>
                <div><dt>Tracking reference</dt><dd>{{ $task->tracking_reference ?: 'Not recorded' }}</dd></div>
            </dl>
        </section>

        <section class="delivery-card">
            <div class="delivery-card-heading"><h2>Proof and customer confirmation</h2></div>
            <dl class="delivery-detail-list">
                <div><dt>Delivery proof</dt><dd>{{ $task->proof_path ? 'Available' : 'Not recorded' }}</dd></div>
                <div><dt>Customer receipt status</dt><dd>{{ $received ? 'Confirmed by customer' : 'Not confirmed' }}</dd></div>
                <div><dt>Customer confirmation</dt><dd>{{ $received?->format('M j, Y g:i A') ?: 'Not recorded' }}</dd></div>
                <div><dt>Confirmation method</dt><dd>{{ $customerConfirmation ? 'Website order tracking' : 'Not recorded' }}</dd></div>
                <div><dt>Feedback / rating</dt><dd>Not recorded</dd></div>
                <div><dt>Delivery notes</dt><dd>{{ $task->delivery_notes ?: 'None' }}</dd></div>
            </dl>
        </section>
    </div>

    <section class="delivery-card mb-3">
        <div class="delivery-card-heading"><h2>Delivery attempts and exceptions</h2></div>
        <div class="table-responsive"><table class="table delivery-attempt-table mb-0"><thead><tr><th>Date and time</th><th>Result</th><th>Failure reason</th><th>Responsible user</th><th>Notes</th></tr></thead><tbody>
        @forelse($task->attempts->sortBy('attempted_at') as $attempt)
            <tr><td>{{ $attempt->attempted_at?->format('M j, Y g:i A') ?: 'Not recorded' }}</td><td>{{ $attempt->result ? \Illuminate\Support\Str::headline($attempt->result) : 'Not recorded' }}</td><td>{{ $attempt->reason ?: 'Not recorded' }}</td><td>{{ $attempt->actor?->name ?: 'Not recorded' }}</td><td>{{ $attempt->note ?: 'None' }}</td></tr>
        @empty
            <tr><td colspan="5" class="text-muted">No delivery attempts were recorded.</td></tr>
        @endforelse
        </tbody></table></div>
    </section>

    <div class="delivery-record-grid mb-3">
        <section class="delivery-card">
            <div class="delivery-card-heading"><h2>Financial summary</h2></div>
            <dl class="delivery-detail-list">
                <div><dt>Product subtotal</dt><dd>RWF {{ number_format((float) ($order?->subtotal ?: 0), 0, '.', ',') }}</dd></div>
                <div><dt>Delivery charges</dt><dd>Not recorded</dd></div>
                <div><dt>Discounts</dt><dd>Not recorded</dd></div>
                <div><dt>Final order total</dt><dd><strong>RWF {{ number_format((float) ($order?->total ?: 0), 0, '.', ',') }}</strong></dd></div>
                <div><dt>Payment status</dt><dd>{{ $order?->payment_status ? \Illuminate\Support\Str::headline($order->payment_status) : 'Not recorded' }}</dd></div>
            </dl>
        </section>
        <section class="delivery-card">
            <div class="delivery-card-heading"><h2>Administrative activity</h2></div>
            @if(($order?->histories?->count() ?: 0) === 0 && $deliveryAudits->isEmpty() && $orderAudits->isEmpty())
                <p class="delivery-empty-copy">No recorded administrative activity.</p>
            @else
                <ul class="delivery-activity-list">
                    @foreach($order?->histories ?? collect() as $history)
                        <li>{{ $history->created_at?->format('M j, Y g:i A') }} — {{ $history->actor?->name ?: 'Administrator' }} changed status to {{ \Illuminate\Support\Str::headline($history->to_status) }}</li>
                    @endforeach
                    @foreach($deliveryAudits->sortByDesc('created_at')->take(12) as $audit)
                        <li>{{ $audit->created_at?->format('M j, Y g:i A') }} — {{ $audit->actor?->name ?: 'System' }} · {{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $audit->action)) }}</li>
                    @endforeach
                    @foreach($orderAudits->sortByDesc('created_at')->take(12) as $audit)
                        <li>{{ $audit->created_at?->format('M j, Y g:i A') }} — {{ $audit->actor?->name ?: 'System' }} · {{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $audit->action)) }}</li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</div>

<style>
.delivery-completed-record{max-width:1180px}.delivery-back-link{color:#697386;font-size:.82rem;text-decoration:none}.delivery-status-delivered{display:inline-flex;border-radius:999px;padding:.38rem .65rem;background:#dcf3e7;color:#18794e;font-size:.75rem;font-weight:700}.delivery-card{border:1px solid #e5e8ef;border-radius:10px;background:#fff}.delivery-card-heading{border-bottom:1px solid #edf0f4;padding:.85rem 1rem}.delivery-card-heading h2{margin:0;font-family:Syne,sans-serif;font-size:.95rem;font-weight:700;color:#202938}.delivery-info-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr))}.delivery-info-grid>div{min-height:78px;border-right:1px solid #edf0f4;border-bottom:1px solid #edf0f4;padding:.75rem 1rem}.delivery-info-grid span,.delivery-detail-list dt{display:block;color:#7a8499;font-size:.72rem}.delivery-info-grid strong,.delivery-detail-list dd{display:block;margin-top:.28rem;color:#30394a;font-size:.82rem}.delivery-products th{border-color:#edf0f4;background:#fafbfc;color:#6d7585;font-size:.72rem}.delivery-products td{border-color:#edf0f4;font-size:.82rem}.delivery-order-meta{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));border-top:1px solid #edf0f4}.delivery-order-meta>div{padding:.75rem 1rem;border-right:1px solid #edf0f4}.delivery-order-meta span{display:block;color:#7a8499;font-size:.72rem}.delivery-order-meta strong{font-size:.82rem}.delivery-record-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.delivery-detail-list{margin:0;padding:.25rem 1rem}.delivery-detail-list div{display:flex;justify-content:space-between;gap:1rem;padding:.65rem 0;border-bottom:1px solid #edf0f4}.delivery-detail-list dd{margin:0;text-align:right}.completed-timeline{margin:0;padding:.8rem 1rem;list-style:none}.completed-timeline li{display:flex;justify-content:space-between;gap:.75rem;padding:.55rem 0;border-bottom:1px solid #edf0f4;font-size:.82rem}.completed-timeline time{color:#818a99}.delivery-attempt-table th{background:#fafbfc;color:#6d7585;font-size:.72rem}.delivery-attempt-table td{font-size:.82rem}.delivery-activity-list{margin:0;padding:.75rem 1rem;list-style:none}.delivery-activity-list li{padding:.55rem 0;border-bottom:1px solid #edf0f4;font-size:.78rem}.delivery-empty-copy{padding:.75rem 1rem;color:#818a99;font-size:.82rem}@media(max-width:991px){.delivery-info-grid,.delivery-order-meta{grid-template-columns:repeat(2,minmax(0,1fr))}.delivery-record-grid{grid-template-columns:1fr}}@media(max-width:575px){.delivery-info-grid,.delivery-order-meta{grid-template-columns:1fr}.delivery-detail-list div{flex-direction:column}.delivery-detail-list dd{text-align:left}}
</style>

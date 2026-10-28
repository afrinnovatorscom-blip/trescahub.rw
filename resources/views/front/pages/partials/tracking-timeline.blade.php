<div class="card mt-4">
    <div class="card-body">
        <p class="mb-1" data-tracking-message>{{ $tracking['message'] }}</p>
        <p class="text-muted small mb-3">Payment: <span data-payment-status>{{ ucfirst(str_replace('_', ' ', $tracking['payment_status'])) }}</span>. This does not by itself mean the order has been delivered.</p>
        <h2 class="h6">Payment records</h2>
        <ul class="mb-0" data-tracking-payments @if(!count($tracking['payments'])) hidden @endif>
            @foreach($tracking['payments'] as $payment)
                <li>{{ ucfirst($payment['type']) }} payment of {{ $tracking['currency'] }} {{ number_format((float) $payment['amount'], 0, '.', ',') }} via {{ ucfirst(str_replace('_', ' ', $payment['method'])) }}@if($payment['recorded_at']) — {{ $payment['recorded_at']->format('M j, Y g:i A') }}@endif</li>
            @endforeach
        </ul>
        <ol class="list-group list-group-numbered" data-tracking-steps>
            @foreach($tracking['timeline']['steps'] as $step)
                <li class="list-group-item d-flex justify-content-between">
                    <span>{{ $step['label'] }}</span>
                    <span class="badge bg-{{ $step['state'] === 'current' ? 'primary' : ($step['state'] === 'complete' ? 'success' : 'light text-dark') }}">{{ $step['state'] }}</span>
                </li>
            @endforeach
        </ol>
        <h2 class="h6 mt-3">Products</h2>
        <ul class="mt-2 mb-0" data-tracking-items>
            @foreach($tracking['items'] as $item)
                <li>{{ $item['name'] }} × {{ $item['quantity'] }} — {{ $tracking['currency'] }} {{ number_format($item['line_total'], 0, '.', ',') }} · {{ $item['fulfillment_label'] }} · {{ $item['assignment_label'] }}</li>
            @endforeach
        </ul>
        <h2 class="h6 mt-3" data-deliveries-heading @if(!count($tracking['deliveries'])) hidden @endif>Deliveries</h2>
        <ul class="mb-0" data-tracking-deliveries @if(!count($tracking['deliveries'])) hidden @endif>
            @foreach($tracking['deliveries'] as $delivery)
                <li>{{ $delivery['reference'] }}: {{ $delivery['status_label'] }}</li>
            @endforeach
        </ul>
        <h2 class="h6 mt-3">Tracking updates</h2>
        <ul class="mb-0" data-tracking-events>
            @foreach($tracking['events'] as $event)
                <li>@if($event['occurred_at']){{ $event['occurred_at']->format('M j, Y g:i A') }} — @endif{{ $event['label'] }}</li>
            @endforeach
        </ul>
    </div>
</div>

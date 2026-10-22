<div class="card mt-4">
    <div class="card-body">
        <p class="mb-1">{{ $tracking['message'] }}</p>
        <p class="text-muted small mb-3">Payment: {{ ucfirst($tracking['payment_status']) }}. This does not by itself mean the order has been delivered.</p>
        <ol class="list-group list-group-numbered">
            @foreach($tracking['timeline']['steps'] as $step)
                <li class="list-group-item d-flex justify-content-between">
                    <span>{{ $step['label'] }}</span>
                    <span class="badge bg-{{ $step['state'] === 'current' ? 'primary' : ($step['state'] === 'complete' ? 'success' : 'light text-dark') }}">{{ $step['state'] }}</span>
                </li>
            @endforeach
        </ol>
        <ul class="mt-3 mb-0">
            @foreach($tracking['items'] as $item)
                <li>{{ $item['name'] }} × {{ $item['quantity'] }} — RWF {{ number_format($item['line_total'], 0, '.', ',') }}</li>
            @endforeach
        </ul>
    </div>
</div>

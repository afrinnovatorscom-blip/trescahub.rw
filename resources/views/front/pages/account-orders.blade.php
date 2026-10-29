@extends('front.layouts.guest')
@section('content')
<main class="py-5"><div class="container">
    <h1 class="h3 mb-3">My Orders</h1>
    @include('marketplace.partials.flash')
    @forelse($orders as $order)
        <a class="list-group-item list-group-item-action d-flex flex-wrap justify-content-between gap-3" href="{{ route('account.orders.show', $order->reference) }}">
            <span class="me-3">
                <strong class="d-block">{{ $order->reference }}</strong>
                <span class="small text-muted">{{ $order->created_at?->format('M j, Y') }}</span>
                <span class="d-block small mt-1">
                    @foreach($order->items as $item)
                        {{ $item->product_name }}@if($item->variation_label && $item->variation_value) ({{ $item->variation_label }}: {{ $item->variation_value }})@endif × {{ $item->quantity }}@if(!$loop->last), @endif
                    @endforeach
                </span>
                <span class="d-block small mt-1">Payment: {{ ucfirst(str_replace('_', ' ', $order->payment_status)) }} via {{ ucfirst(str_replace('_', ' ', $order->payment_method ?? 'not specified')) }} · Delivery: {{ $order->deliveries->isEmpty() ? 'Not created' : $order->deliveries->map(fn ($delivery) => \App\Support\CustomerTracking::deliveryLabel($delivery->status))->unique()->join(', ') }}</span>
            </span>
            <span class="text-end ms-auto">
                <strong class="d-block">RWF {{ number_format((float) $order->total, 0, '.', ',') }}</strong>
                <span>{{ \App\Support\CustomerTracking::label($order->fulfillment_status) }}</span>
                <span class="d-block small">View details</span>
            </span>
        </a>
    @empty
        <p class="text-muted">You have no orders yet.</p>
    @endforelse
    <div class="mt-3">{{ $orders->links() }}</div>
</div></main>
@endsection

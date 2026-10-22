@extends('front.layouts.guest')
@section('content')
<main class="py-5"><div class="container">
    <h1 class="h3 mb-3">My orders</h1>
    @include('marketplace.partials.flash')
    @forelse($orders as $order)
        <a class="list-group-item list-group-item-action d-flex justify-content-between" href="{{ route('account.orders.show', $order->reference) }}">
            <span>{{ $order->reference }}</span>
            <span>{{ \App\Support\CustomerTracking::label($order->fulfillment_status) }}</span>
        </a>
    @empty
        <p class="text-muted">You have no orders yet.</p>
    @endforelse
    <div class="mt-3">{{ $orders->links() }}</div>
</div></main>
@endsection

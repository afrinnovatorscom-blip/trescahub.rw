@extends('front.layouts.guest')

@section('title', 'My Requests')

@section('content')
<main class="py-5">
    <div class="container" style="max-width: 900px;">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <h1 class="h3 mb-0">My Requests</h1>
            <div class="d-flex gap-2">
                <a href="{{ route('account.profile') }}" class="btn btn-outline-success">My profile</a>
                <a href="{{ route('account.orders') }}" class="btn btn-outline-success">My orders</a>
            </div>
        </div>

        <section class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-3">Product requests</h2>
                @forelse($productRequests as $request)
                    <article class="border rounded-3 p-3 mb-2">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <strong>{{ $request->reference }}</strong>
                            <span class="badge text-bg-light">{{ str($request->status)->replace('_', ' ')->title() }}</span>
                        </div>
                        <p class="mb-1">{{ $request->product_name }} &middot; Quantity {{ $request->quantity }}</p>
                        <small class="text-muted">Submitted {{ $request->created_at->format('M j, Y') }}</small>
                    </article>
                @empty
                    <p class="text-muted mb-0">You have no product requests.</p>
                @endforelse
            </div>
        </section>

        <section class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold mb-3">Service requests</h2>
                @forelse($serviceRequests as $request)
                    <article class="border rounded-3 p-3 mb-2">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <strong>{{ $request->service_category ?: 'Service request' }}</strong>
                            <span class="badge text-bg-light">{{ str($request->status)->replace('_', ' ')->title() }}</span>
                        </div>
                        <p class="mb-1">{{ $request->description }}</p>
                        <small class="text-muted">Submitted {{ $request->created_at->format('M j, Y') }}</small>
                    </article>
                @empty
                    <p class="text-muted mb-0">You have no service requests.</p>
                @endforelse
            </div>
        </section>
    </div>
</main>
@endsection

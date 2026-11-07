@extends('front.layouts.admin')
@section('page-title', 'Reviews')
@section('content')
<style>
    .reviews-admin { color: #0f172a; max-width: 1400px; }
    .reviews-admin-heading { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 1rem; align-items: flex-end; margin-bottom: 1.25rem; }
    .reviews-admin-heading h1 { font-size: 1.35rem; font-weight: 700; margin: 0; }
    .reviews-admin-heading p { color: #64748b; font-size: .875rem; margin: .25rem 0 0; }
    .reviews-stats { display: flex; gap: .75rem; flex-wrap: wrap; }
    .reviews-stat { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: .55rem .9rem; font-size: .8rem; color: #64748b; }
    .reviews-stat strong { display: block; color: #0f172a; font-size: 1.1rem; }
    .reviews-filters { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1rem; }
    .reviews-filters .form-control, .reviews-filters .form-select { width: auto; min-width: 150px; font-size: .86rem; }
    .reviews-card { border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; padding: 1rem 1.1rem; margin-bottom: .75rem; }
    .reviews-card.is-hidden { background: #f8fafc; }
    .reviews-card-top { display: flex; flex-wrap: wrap; gap: .4rem 1rem; align-items: center; font-size: .82rem; color: #64748b; }
    .reviews-card-top a { color: #0f172a; font-weight: 600; text-decoration: none; }
    .reviews-stars { color: #f5a623; letter-spacing: .05em; }
    .reviews-stars .is-off { color: #d9d9d9; }
    .reviews-status { border-radius: 99px; padding: .1rem .55rem; font-size: .72rem; font-weight: 600; }
    .reviews-status.is-published { background: #dcfce7; color: #166534; }
    .reviews-status.is-hidden { background: #fee2e2; color: #991b1b; }
    .reviews-card h3 { font-size: .95rem; font-weight: 700; margin: .6rem 0 .2rem; }
    .reviews-card p { font-size: .88rem; color: #334155; margin: .3rem 0 0; white-space: pre-line; }
    .reviews-actions { display: flex; flex-wrap: wrap; gap: .75rem; align-items: flex-start; margin-top: .85rem; padding-top: .85rem; border-top: 1px solid #eef2f7; }
    .reviews-reply-form { flex: 1 1 360px; display: flex; gap: .5rem; align-items: flex-start; }
    .reviews-reply-form textarea { font-size: .85rem; }
</style>

<div class="reviews-admin">
    <div class="reviews-admin-heading">
        <div>
            <h1><i class="bi bi-star"></i> Product reviews</h1>
            <p>Reviews come from customers whose order was delivered. Hide anything abusive or off-topic, and reply publicly as TRESCA Hub.</p>
        </div>
        <div class="reviews-stats">
            <div class="reviews-stat"><strong>{{ (int) ($counts['published'] ?? 0) }}</strong> Published</div>
            <div class="reviews-stat"><strong>{{ (int) ($counts['hidden'] ?? 0) }}</strong> Hidden</div>
            <div class="reviews-stat"><strong>{{ $averageRating > 0 ? number_format($averageRating, 1) : '—' }}</strong> Average rating</div>
        </div>
    </div>

    @include('marketplace.partials.flash')

    <form method="GET" class="reviews-filters">
        <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Search product, customer, text">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            <option value="published" @selected(($filters['status'] ?? '') === 'published')>Published</option>
            <option value="hidden" @selected(($filters['status'] ?? '') === 'hidden')>Hidden</option>
        </select>
        <select name="rating" class="form-select">
            <option value="">All ratings</option>
            @foreach([5, 4, 3, 2, 1] as $star)
                <option value="{{ $star }}" @selected((int) ($filters['rating'] ?? 0) === $star)>{{ $star }} star{{ $star > 1 ? 's' : '' }}</option>
            @endforeach
        </select>
        <button class="btn btn-dark btn-sm">Filter</button>
        @if(array_filter($filters))
            <a href="{{ route('marketplace.reviews.index') }}" class="btn btn-link btn-sm">Clear</a>
        @endif
    </form>

    @forelse($reviews as $review)
        <article class="reviews-card {{ $review->status === 'hidden' ? 'is-hidden' : '' }}">
            <div class="reviews-card-top">
                <span class="reviews-stars" aria-label="{{ $review->rating }} out of 5">
                    @for($i = 1; $i <= 5; $i++)<span class="{{ $i > $review->rating ? 'is-off' : '' }}">★</span>@endfor
                </span>
                <span class="reviews-status is-{{ $review->status }}">{{ ucfirst($review->status) }}</span>
                @if($review->product)
                    <a href="{{ route('shop-products.show', $review->product) }}#reviews" target="_blank" rel="noopener">{{ $review->product->product_name }}</a>
                @endif
                <span>by {{ $review->customer?->name ?? 'Deleted customer' }}</span>
                @if($review->order)
                    <span>Order <a href="{{ route('marketplace.orders.show', $review->order) }}">{{ $review->order->reference }}</a></span>
                @endif
                <span class="ms-auto">{{ $review->created_at->format('M j, Y g:i A') }}</span>
            </div>
            @if($review->title)<h3>{{ $review->title }}</h3>@endif
            <p>{{ $review->body }}</p>

            <div class="reviews-actions">
                <form method="POST" action="{{ route('marketplace.reviews.update', $review) }}">
                    @csrf
                    @method('PATCH')
                    @if($review->status === 'published')
                        <input type="hidden" name="status" value="hidden">
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-eye-slash"></i> Hide</button>
                    @else
                        <input type="hidden" name="status" value="published">
                        <button class="btn btn-outline-success btn-sm"><i class="bi bi-eye"></i> Publish</button>
                    @endif
                </form>
                <form method="POST" action="{{ route('marketplace.reviews.update', $review) }}" class="reviews-reply-form">
                    @csrf
                    @method('PATCH')
                    <textarea name="admin_reply" rows="2" maxlength="1000" class="form-control" placeholder="Public reply from TRESCA Hub (optional)">{{ $review->admin_reply }}</textarea>
                    <button class="btn btn-dark btn-sm text-nowrap">{{ $review->admin_reply ? 'Update reply' : 'Reply' }}</button>
                </form>
            </div>
        </article>
    @empty
        <div class="text-center text-muted py-5 border rounded-3 bg-white">No reviews match these filters.</div>
    @endforelse

    <div class="mt-3">{{ $reviews->links() }}</div>
</div>
@endsection

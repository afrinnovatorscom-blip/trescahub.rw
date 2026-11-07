{{--
    Ratings & reviews section of the product page.
    Needs: $shopProduct, $reviewSummary, $reviews, $reviewSort, $reviewRating, $ownReview, $canReview
--}}
@php
    $reviewCustomer = auth('customers')->user();
    $showReviewForm = $canReview || $ownReview;
    $openForm = $errors->review->any();
    $formReview = $ownReview;
    $selectedStars = (int) old('rating', $formReview?->rating ?? 0);
    $reviewBaseUrl = route('shop-products.show', $shopProduct);
@endphp

<section id="reviews" class="pd-section">
    <div class="rv-head">
        <h2 class="pd-section-title mb-0">Ratings &amp; Reviews</h2>
        @if($showReviewForm)
            <button type="button" class="rv-write-btn" data-review-toggle aria-expanded="{{ $openForm ? 'true' : 'false' }}" aria-controls="write-review">
                <i class="bi bi-pencil-square"></i> {{ $ownReview ? 'Edit your review' : 'Write a review' }}
            </button>
        @endif
    </div>

    @if(session('review_status'))
        <div class="alert alert-success py-2 mt-3 mb-0" role="status">{{ session('review_status') }}</div>
    @endif

    {{-- Summary --}}
    <div class="rv-summary">
        <div class="rv-score">
            <div class="rv-score-number">{{ $reviewSummary['count'] > 0 ? number_format($reviewSummary['average'], 1) : '—' }}</div>
            @include('front.pages.partials.rating-stars', ['rating' => $reviewSummary['average'], 'size' => '1.25rem'])
            <div class="rv-score-count">
                {{ $reviewSummary['count'] }} {{ \Illuminate\Support\Str::plural('review', $reviewSummary['count']) }}
                <span class="rv-verified"><i class="bi bi-patch-check-fill"></i> Verified purchases</span>
            </div>
        </div>
        <div class="rv-bars">
            @foreach($reviewSummary['distribution'] as $star => $total)
                @php $percent = $reviewSummary['count'] > 0 ? round($total / $reviewSummary['count'] * 100) : 0; @endphp
                <a class="rv-bar-row {{ $reviewRating === $star ? 'is-active' : '' }}"
                   href="{{ $reviewRating === $star ? $reviewBaseUrl.'#reviews' : $reviewBaseUrl.'?'.http_build_query(['reviews_rating' => $star, 'reviews_sort' => $reviewSort]).'#reviews' }}"
                   aria-label="{{ $star }} star reviews: {{ $total }}">
                    <span class="rv-bar-label">{{ $star }} <i class="bi bi-star-fill"></i></span>
                    <span class="rv-bar-track"><span class="rv-bar-fill" style="width: {{ $percent }}%"></span></span>
                    <span class="rv-bar-total">{{ $total }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Write / edit form --}}
    @if($showReviewForm)
        <div id="write-review" class="rv-form-wrap" @if(! $openForm) hidden @endif>
            @if(! $canReview && $ownReview)
                <p class="small text-muted">You can no longer edit this review because the order is no longer marked as received.</p>
            @else
                <form method="POST" action="{{ route('shop-products.reviews.store', $shopProduct) }}" class="rv-form" novalidate>
                    @csrf
                    <fieldset class="rv-star-input">
                        <legend>Your rating</legend>
                        <div class="rv-star-picker" role="radiogroup" aria-label="Rating">
                            @for($star = 5; $star >= 1; $star--)
                                <input type="radio" id="rv-star-{{ $star }}" name="rating" value="{{ $star }}" @checked($selectedStars === $star) required>
                                <label for="rv-star-{{ $star }}" title="{{ $star }} {{ \Illuminate\Support\Str::plural('star', $star) }}"><i class="bi bi-star-fill"></i></label>
                            @endfor
                        </div>
                        @error('rating', 'review')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </fieldset>

                    <label class="form-label" for="review-title">Title <span class="text-muted fw-normal">(optional)</span></label>
                    <input id="review-title" name="title" type="text" maxlength="120" class="form-control @error('title', 'review') is-invalid @enderror"
                           value="{{ old('title', $formReview?->title) }}" placeholder="Summarise your experience">
                    @error('title', 'review')<div class="invalid-feedback">{{ $message }}</div>@enderror

                    <label class="form-label mt-3" for="review-body">Your review</label>
                    <textarea id="review-body" name="body" rows="4" maxlength="2000" required
                              class="form-control @error('body', 'review') is-invalid @enderror"
                              placeholder="What did you like or dislike? How was the quality and delivery?">{{ old('body', $formReview?->body) }}</textarea>
                    @error('body', 'review')<div class="invalid-feedback">{{ $message }}</div>@enderror

                    <div class="rv-form-actions">
                        <button type="submit" class="pd-btn pd-btn-primary">{{ $ownReview ? 'Update review' : 'Post review' }}</button>
                        <button type="button" class="btn btn-link text-muted" data-review-toggle>Cancel</button>
                    </div>
                </form>
                @if($ownReview)
                    <form method="POST" action="{{ route('shop-products.reviews.destroy', $shopProduct) }}" class="mt-2"
                          onsubmit="return confirm('Delete your review?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-link btn-sm text-danger p-0">Delete my review</button>
                    </form>
                @endif
            @endif
        </div>
    @elseif(! $reviewCustomer)
        <p class="rv-note"><i class="bi bi-info-circle"></i>
            <a href="{{ $reviewBaseUrl }}?auth_required=1#reviews">Sign in</a> to review this product after you receive it.</p>
    @else
        <p class="rv-note"><i class="bi bi-info-circle"></i> Only customers who received this product can review it.</p>
    @endif

    @if($ownReview && $ownReview->status === 'hidden')
        <p class="rv-note text-warning-emphasis"><i class="bi bi-eye-slash"></i> Your review is currently hidden by our moderators.</p>
    @endif

    {{-- Toolbar --}}
    @if($reviewSummary['count'] > 0)
        <form method="GET" action="{{ $reviewBaseUrl }}#reviews" class="rv-toolbar">
            <div class="rv-filter-chips">
                <a href="{{ $reviewBaseUrl }}?{{ http_build_query(['reviews_sort' => $reviewSort]) }}#reviews" class="rv-chip {{ $reviewRating === null ? 'is-active' : '' }}">All</a>
                @foreach([5, 4, 3, 2, 1] as $star)
                    <a href="{{ $reviewBaseUrl }}?{{ http_build_query(['reviews_rating' => $star, 'reviews_sort' => $reviewSort]) }}#reviews"
                       class="rv-chip {{ $reviewRating === $star ? 'is-active' : '' }}">{{ $star }} <i class="bi bi-star-fill"></i></a>
                @endforeach
            </div>
            @if($reviewRating)<input type="hidden" name="reviews_rating" value="{{ $reviewRating }}">@endif
            <label class="rv-sort">
                <span>Sort by</span>
                <select name="reviews_sort" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach(\App\Services\ProductReviewService::SORTS as $value => $label)
                        <option value="{{ $value }}" @selected($reviewSort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </form>
    @endif

    {{-- List --}}
    <div class="rv-list">
        @forelse($reviews as $review)
            <article class="rv-item">
                <div class="rv-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($review->reviewer_name, 0, 1)) }}</div>
                <div class="rv-body">
                    <div class="rv-meta">
                        <strong>{{ $review->reviewer_name }}</strong>
                        <span class="rv-badge"><i class="bi bi-bag-check"></i> Verified purchase</span>
                        <time datetime="{{ $review->created_at->toIso8601String() }}">{{ $review->created_at->format('M j, Y') }}</time>
                    </div>
                    @include('front.pages.partials.rating-stars', ['rating' => $review->rating, 'size' => '.95rem'])
                    @if($review->title)<h3 class="rv-title">{{ $review->title }}</h3>@endif
                    <p class="rv-text">{!! nl2br(e($review->body)) !!}</p>
                    @if($review->admin_reply)
                        <div class="rv-reply">
                            <div class="rv-reply-head"><i class="bi bi-shop"></i> Response from TRESCA Hub</div>
                            <p>{!! nl2br(e($review->admin_reply)) !!}</p>
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="rv-empty">
                <i class="bi bi-chat-square-text"></i>
                <p class="mb-0">
                    {{ $reviewRating ? 'No '.$reviewRating.'-star reviews yet.' : 'No reviews yet. Customers who buy this product can share their experience here.' }}
                </p>
            </div>
        @endforelse
    </div>

    @if($reviews->hasPages())
        <div class="rv-pagination">{{ $reviews->links('pagination::bootstrap-5') }}</div>
    @endif
</section>

<style>
    .rv-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; }
    .rv-write-btn { border: 1px solid #222; background: #fff; color: #222; border-radius: 999px; padding: .45rem 1rem; font-weight: 600; font-size: .86rem; }
    .rv-write-btn:hover { background: #222; color: #fff; }
    .rv-summary { display: grid; grid-template-columns: 220px minmax(0, 1fr); gap: 1.5rem; align-items: center; margin-top: 1.1rem; padding: 1.1rem; background: #fafafa; border-radius: 10px; }
    .rv-score { text-align: center; }
    .rv-score-number { font-size: 2.6rem; font-weight: 800; line-height: 1; margin-bottom: .3rem; font-family: 'Space Grotesk', sans-serif; }
    .rv-score-count { font-size: .8rem; color: #767676; margin-top: .35rem; }
    .rv-verified { display: block; color: #16803c; margin-top: .15rem; }
    .rv-bars { display: grid; gap: .35rem; max-width: 460px; }
    .rv-bar-row { display: grid; grid-template-columns: 38px minmax(0, 1fr) 32px; gap: .6rem; align-items: center; text-decoration: none; color: #555; font-size: .8rem; border-radius: 4px; }
    .rv-bar-row:hover .rv-bar-track, .rv-bar-row.is-active .rv-bar-track { outline: 1px solid #f5a623; }
    .rv-bar-label i { color: #f5a623; font-size: .7rem; }
    .rv-bar-track { height: 8px; background: #e8e8e8; border-radius: 99px; overflow: hidden; }
    .rv-bar-fill { display: block; height: 100%; background: #f5a623; border-radius: 99px; }
    .rv-bar-total { text-align: right; }
    .rv-note { font-size: .86rem; color: #555; margin: 1rem 0 0; }
    .rv-note a { color: #16803c; font-weight: 600; }

    .rv-form-wrap { margin-top: 1rem; padding: 1.1rem; border: 1px solid #e8e8e8; border-radius: 10px; }
    .rv-form .form-label { font-size: .86rem; font-weight: 600; }
    .rv-form .form-control { border-radius: 8px; }
    .rv-star-input { border: 0; padding: 0; margin: 0 0 1rem; }
    .rv-star-input legend { font-size: .86rem; font-weight: 600; margin-bottom: .3rem; }
    .rv-star-picker { display: inline-flex; flex-direction: row-reverse; gap: .2rem; }
    .rv-star-picker input { position: absolute; opacity: 0; width: 1px; height: 1px; }
    .rv-star-picker label { font-size: 1.7rem; color: #d9d9d9; cursor: pointer; transition: color .1s, transform .1s; }
    .rv-star-picker label:hover, .rv-star-picker label:hover ~ label,
    .rv-star-picker input:checked ~ label { color: #f5a623; }
    .rv-star-picker label:hover { transform: scale(1.1); }
    .rv-star-picker input:focus-visible + label { outline: 2px solid #16803c; outline-offset: 2px; border-radius: 4px; }
    .rv-form-actions { display: flex; align-items: center; gap: .5rem; margin-top: 1rem; }
    .rv-form-actions .pd-btn { width: auto; padding: 0 1.5rem; }

    .rv-toolbar { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: .75rem; margin-top: 1.25rem; padding-bottom: .9rem; border-bottom: 1px solid #eee; }
    .rv-filter-chips { display: flex; flex-wrap: wrap; gap: .4rem; }
    .rv-chip { border: 1px solid #ddd; border-radius: 999px; padding: .25rem .75rem; font-size: .8rem; color: #444; text-decoration: none; }
    .rv-chip i { color: #f5a623; font-size: .7rem; }
    .rv-chip.is-active, .rv-chip:hover { border-color: #222; color: #222; background: #f5f5f5; }
    .rv-sort { display: flex; align-items: center; gap: .5rem; font-size: .8rem; color: #767676; margin: 0; }
    .rv-sort select { width: auto; border-radius: 999px; }

    .rv-list { display: grid; }
    .rv-item { display: flex; gap: .85rem; padding: 1.1rem 0; border-bottom: 1px solid #f0f0f0; }
    .rv-avatar { flex: 0 0 38px; height: 38px; border-radius: 50%; background: #e7f6ec; color: #16803c; font-weight: 700; display: flex; align-items: center; justify-content: center; }
    .rv-body { min-width: 0; flex: 1; }
    .rv-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .3rem .75rem; font-size: .82rem; margin-bottom: .3rem; }
    .rv-meta time { color: #999; margin-left: auto; }
    .rv-badge { color: #16803c; font-size: .75rem; }
    .rv-title { font-size: .95rem; font-weight: 700; margin: .45rem 0 .2rem; }
    .rv-text { font-size: .9rem; line-height: 1.6; color: #444; margin: .35rem 0 0; overflow-wrap: anywhere; }
    .rv-reply { margin-top: .75rem; padding: .7rem .9rem; background: #f6f8f6; border-left: 3px solid #16803c; border-radius: 6px; font-size: .85rem; }
    .rv-reply-head { font-weight: 700; font-size: .8rem; margin-bottom: .25rem; }
    .rv-reply p { margin: 0; color: #444; }
    .rv-empty { text-align: center; color: #767676; padding: 2rem 1rem; font-size: .9rem; }
    .rv-empty i { font-size: 1.8rem; display: block; margin-bottom: .4rem; color: #c8c8c8; }
    .rv-pagination { margin-top: 1rem; }
    .rv-pagination nav { display: flex; justify-content: center; }

    @media (max-width: 767.98px) {
        .rv-summary { grid-template-columns: 1fr; gap: 1rem; }
        .rv-meta time { margin-left: 0; width: 100%; }
    }
</style>

<script>
    (() => {
        const panel = document.getElementById('write-review');
        document.querySelectorAll('[data-review-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                if (!panel) return;
                panel.hidden = !panel.hidden;
                document.querySelectorAll('.rv-write-btn').forEach((toggle) => toggle.setAttribute('aria-expanded', String(!panel.hidden)));
                if (!panel.hidden) panel.querySelector('input[name="rating"]')?.focus();
            });
        });
    })();
</script>

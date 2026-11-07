{{-- Star display. Params: $rating (0-5, decimals allowed), optional $size (css font-size) --}}
@php
    $ratingValue = max(0, min(5, (float) ($rating ?? 0)));
    $starSize = $size ?? '1rem';
@endphp
<span class="rating-stars" style="--star-size: {{ $starSize }}" role="img" aria-label="Rated {{ number_format($ratingValue, 1) }} out of 5">
    <span class="rating-stars-base" aria-hidden="true">★★★★★</span>
    <span class="rating-stars-fill" style="width: {{ $ratingValue / 5 * 100 }}%" aria-hidden="true">★★★★★</span>
</span>
@once
    @push('styles')
    <style>
        .rating-stars { position: relative; display: inline-block; font-size: var(--star-size); line-height: 1; letter-spacing: .05em; white-space: nowrap; vertical-align: middle; }
        .rating-stars-base { color: #d9d9d9; }
        .rating-stars-fill { position: absolute; inset: 0 auto 0 0; overflow: hidden; color: #f5a623; }
    </style>
    @endpush
@endonce

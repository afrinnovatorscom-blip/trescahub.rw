@php
    $services = \App\Models\Category::with(['services' => function($q) {
        $q->where('is_in_stock', 1)->limit(1);
    }])
   ->orderBy('title', 'asc')
    ->get()
    ->filter(fn($cat) => $cat->services->isNotEmpty());

    $categoryIcons = [
        'engineering'  => '🔧',
        'graphic'      => '🎨',
        'construction' => '🏗️',
        'shopping'     => '🛒',
        'refreshment'  => '🥤',
        'event'        => '📅',
        'design'       => '✏️',
        'technical'    => '⚙️',
        'works'        => '🔨',
        'supplies'     => '📦',
        'support'      => '🆘',
    ];
@endphp

@if($services->isNotEmpty())
<section class="py-5 bg-light">
    <div class="container-xl px-4 px-lg-5">

        {{-- Section Header --}}
        <div class="text-center mb-5">
            <h2 class="fw-bold mb-2 fs-1 text-dark">
                Our Service Stores
            </h2>
            <p class="mx-auto text-secondary" style="max-width: 600px;">
                Five specialized stores, one powerful platform. Find exactly what you need.
            </p>
        </div>

        {{-- Cards Row: 1 col on mobile, 2 on sm, 3 on md, 5 on lg+ --}}
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-3 justify-content-center">

            @foreach($services as $category)
                @php
                    $service = $category->services->first();
                    $catKey  = strtolower($category->title ?? '');
                    $catIcon = '📦';
                    foreach($categoryIcons as $key => $iconEmoji) {
                        if (str_contains($catKey, $key)) {
                            $catIcon = $iconEmoji;
                            break;
                        }
                    }
                @endphp

                <div class="col">
                    <a href="{{ route('services.category', $category->slug) }}"
                       class="card h-100 text-decoration-none border-0 shadow-sm rounded-3 overflow-hidden">

                        {{-- Image --}}
                        <div class="position-relative overflow-hidden" style="height: 170px;">
                            @if($service->thumbnail)
                                <img src="{{ asset('' . $service->thumbnail) }}"
                                     class="w-100 h-100"
                                     alt="{{ $category->title }}"
                                     style="object-fit: cover; object-position: center; transition: transform 0.35s ease;">
                            @else
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light">
                                    <span class="fs-1">📦</span>
                                </div>
                            @endif

                            {{-- Icon Badge --}}
                            <div class="position-absolute bottom-0 start-0 mb-2 ms-2">
                                <span class="d-inline-flex align-items-center justify-content-center bg-white rounded-circle border shadow-sm"
                                      style="width:36px; height:36px;">
                                    <span style="font-size:1.1rem; line-height:1;">{{ $catIcon }}</span>
                                </span>
                            </div>
                        </div>

                        {{-- Card Body --}}
                        <div class="card-body text-center d-flex flex-column align-items-center px-3 py-3">

                            <h3 class="card-title fw-bold text-dark mb-1" style="font-size: 14px; line-height: 1.4;">
                                {{ $category->title }}
                            </h3>

                            <p class="text-uppercase text-secondary mb-0" style="font-size: 10px; letter-spacing: 0.4px;">
                                TRESCA {{ strtoupper(explode(' ', trim($category->title))[0]) }} Ltd
                            </p>

                            <span class="d-inline-flex align-items-center gap-1 mt-3 fw-semibold text-success"
                                  style="font-size: 13px;">
                                Shop Service
                                <i class="bi bi-arrow-right" style="font-size: 11px;"></i>
                            </span>

                        </div>
                    </a>
                </div>

            @endforeach
        </div>

    </div>
</section>
@endif
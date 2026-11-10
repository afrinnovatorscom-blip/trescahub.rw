@php
    $heroSlides = \App\Models\Background::all()->values();
    $heroSlideCount = $heroSlides->count();

    if ($heroSlides->isEmpty()) {
        $heroSlides = collect([null]);
    }

    $shopUrl = route('shop');
    $servicesUrl = route('services');
@endphp

<section id="heroCarousel" class="th-home-hero" aria-roledescription="carousel" aria-label="Featured Tresca Hub content">
    <span class="th-hero-dots" aria-hidden="true"></span>
    <div class="th-hero-carousel-inner">
        <div class="th-hero-stage">
            <div class="th-hero-copy">
                <div class="th-hero-text-slides">
        @foreach($heroSlides as $index => $slide)
            @php
                $rawTitle = $slide?->title;
                $rawDescription = $slide?->description;
                $title = is_array($rawTitle) ? (reset($rawTitle) ?: '') : ($rawTitle ?: '');
                $description = is_array($rawDescription) ? (reset($rawDescription) ?: '') : ($rawDescription ?: '');
                $title = trim($title) ?: 'Discover Our Latest Products';
                $description = trim($description) ?: 'Shop the best products with great quality, competitive prices and fast delivery.';
                $titleWords = preg_split('/\s+/', $title);
                $leadWords = array_slice($titleWords, 0, max(1, (int) ceil(count($titleWords) / 2)));
                $highlightWords = array_slice($titleWords, count($leadWords));
                $highlightWord = array_shift($highlightWords) ?? '';
            @endphp

            <article class="th-hero-slide {{ $index === 0 ? 'is-active' : '' }}"
                     data-slide="{{ $index }}"
                     aria-hidden="{{ $index === 0 ? 'false' : 'true' }}">
                    <div class="th-hero-slide-text">
                        <h1 class="th-hero-title" aria-label="{{ $title }}">
                            <span class="th-hero-title-line">{{ implode(' ', $leadWords) }}</span>
                            <span class="th-hero-title-line">
                                @if($highlightWord)
                                    <span class="th-hero-highlight">{{ $highlightWord }}</span>
                                @endif
                                @if(count($highlightWords))
                                    {{ implode(' ', $highlightWords) }}
                                @endif
                            </span>
                        </h1>

                        <p class="th-hero-description">{{ $description }}</p>
                    </div>
            </article>
        @endforeach
                </div>

                <div class="th-hero-actions">
                    <a class="th-hero-button th-hero-button-primary" href="{{ $shopUrl }}">
                        <i class="bi bi-cart-fill" aria-hidden="true"></i>
                        <span>Shop Now</span>
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                    <a class="th-hero-button th-hero-button-secondary" href="{{ $servicesUrl }}">
                        <i class="bi bi-briefcase-fill" aria-hidden="true"></i>
                        <span>Browse Services</span>
                    </a>
                </div>

                <div class="th-hero-support">
                    <i class="bi bi-building" aria-hidden="true"></i>
                    <span>Powered by TRESCA Group</span>
                </div>
            </div>

            <div class="th-hero-product">
                <img src="{{ asset('assets/hero-product-composition.png') }}"
                     alt="Headphones, smartphone, smartwatch and wireless earbuds"
                     width="1536"
                     height="768"
                     fetchpriority="high"
                     decoding="async">
            </div>
        </div>
    </div>

    @if($heroSlideCount > 1)
        <div class="th-hero-controls" aria-label="Hero slide indicators">
            <div class="th-hero-indicators">
                @foreach($heroSlides as $index => $slide)
                    <button class="th-hero-indicator {{ $index === 0 ? 'is-active' : '' }}"
                            type="button"
                            data-hero-indicator="{{ $index }}"
                            aria-label="Go to slide {{ $index + 1 }}"
                            aria-current="{{ $index === 0 ? 'true' : 'false' }}"></button>
                @endforeach
            </div>
        </div>
    @endif
</section>

<style>
.th-home-hero {
    --th-green: #16a34a;
    --th-green-dark: #078443;
    --th-ink: #172033;
    --th-muted: #5b6475;

    position: relative;
    isolation: isolate;
    min-height: 610px;
    overflow: hidden;
    color: var(--th-ink);
    background:
        radial-gradient(ellipse at 47% 4%, rgba(22, 163, 74, .07), transparent 25%),
        linear-gradient(115deg, #fff 0%, #fff 49%, #f1fbf5 100%);
}

/* Decorative background */
.th-home-hero::before {
    position: absolute;
    z-index: 1;
    top: -13%;
    right: -9%;
    width: 59%;
    height: 126%;
    border-radius: 54% 0 0 54% / 100% 0 0 100%;
    background: linear-gradient(135deg, #20c465 0%, #16a34a 48%, #008b47 100%);
    content: "";
    pointer-events: none;
}

.th-home-hero::after {
    position: absolute;
    z-index: 1;
    top: -33%;
    right: 20%;
    width: 34%;
    aspect-ratio: 1;
    border: 24px solid rgba(22, 163, 74, .045);
    border-radius: 50%;
    content: "";
    pointer-events: none;
}

.th-hero-dots {
    position: absolute;
    z-index: 2;
    top: 7%;
    right: 2.5%;
    width: 118px;
    height: 76px;
    background: radial-gradient(circle, rgba(255,255,255,.2) 3px, transparent 4px) 0 0 / 18px 18px;
    pointer-events: none;
}

/* Carousel */
.th-hero-carousel-inner {
    position: relative;
    min-height: inherit;
}

.th-hero-text-slides {
    display: grid;
    min-width: 0;
}

.th-hero-slide {
    position: relative;
    grid-area: 1 / 1;
    visibility: hidden;
    pointer-events: none;
    transition: visibility 0s linear .6s;
}

.th-hero-slide.is-active {
    z-index: 1;
    visibility: visible;
    pointer-events: auto;
    transition-delay: 0s;
}

.th-hero-slide-text {
    position: relative;
    z-index: 1;
    background: transparent;
    box-shadow: none;
    opacity: 0;
    text-shadow: none;
    transform: translateY(8px);
    filter: none;
    transition:
        opacity .6s cubic-bezier(.22, 1, .36, 1),
        transform .6s cubic-bezier(.22, 1, .36, 1);
}

.th-hero-slide-text * {
    box-shadow: none;
    text-shadow: none;
}

.th-hero-slide.is-active .th-hero-slide-text {
    opacity: 1;
    transform: translateY(0);
}

/* Main hero layout */
.th-hero-stage {
    position: relative;
    z-index: 3;
    display: grid;
    grid-template-columns: minmax(0, .96fr) minmax(0, 1.04fr);
    align-items: center;
    gap: 12px;
    width: min(100% - 80px, 1240px);
    min-height: 610px;
    margin: 0 auto;
    padding: 48px 0 65px;
}

/* Hero text */
.th-hero-copy {
    position: relative;
    z-index: 3;
    max-width: 610px;
    min-width: 0;
    padding-bottom: 5px;
}

.th-hero-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 23px;
    padding: 12px 20px;
    border-radius: 999px;
    color: #078443;
    background: #d9f4e5;
    font-family: "DM Sans", sans-serif;
    font-size: .96rem;
    font-weight: 700;
}

.th-hero-eyebrow i {
    font-size: 1.1rem;
}

.th-hero-title {
    margin: 0 0 17px;
    color: var(--th-ink);
    font-family: "Space Grotesk", sans-serif;
    font-size: clamp(3rem, 5.1vw, 4.65rem);
    font-weight: 700;
    letter-spacing: -.055em;
    line-height: .99;
    overflow-wrap: break-word;
}

.th-hero-title-line {
    display: block;
}

.th-hero-highlight {
    color: var(--th-green);
}

.th-hero-description {
    max-width: 510px;
    margin: 0 0 30px;
    color: var(--th-muted);
    font-family: "DM Sans", sans-serif;
    font-size: clamp(1.05rem, 1.45vw, 1.25rem);
    font-weight: 400;
    line-height: 1.55;
}

/* Hero buttons */
.th-hero-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 15px;
}

.th-hero-button {
    display: inline-flex;
    min-height: 54px;
    align-items: center;
    justify-content: center;
    gap: 11px;
    padding: 0 23px;
    border: 1px solid var(--th-green);
    border-radius: 17px;
    font-family: "DM Sans", sans-serif;
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.2;
    text-decoration: none;
    transition:
        background-color .2s ease,
        color .2s ease,
        box-shadow .2s ease,
        transform .2s ease;
}

.th-hero-button:hover {
    transform: translateY(-2px);
}

.th-hero-button-primary {
    min-width: 203px;
    color: #fff;
    background: var(--th-green);
    box-shadow: 0 10px 24px rgba(22, 163, 74, .18);
}

.th-hero-button-primary:hover {
    color: #fff;
    background: var(--th-green-dark);
    box-shadow: 0 13px 28px rgba(22, 163, 74, .25);
}

.th-hero-button-secondary {
    min-width: 225px;
    color: var(--th-ink);
    background: rgba(255, 255, 255, .9);
}

.th-hero-button-secondary i {
    color: var(--th-green);
}

.th-hero-button-secondary:hover {
    color: var(--th-green-dark);
    background: #f0fbf4;
}

/* Support text */
.th-hero-support {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 37px;
    color: #414b5e;
    font-family: "DM Sans", sans-serif;
    font-size: .98rem;
    font-weight: 600;
}

.th-hero-support::before {
    width: 1px;
    height: 25px;
    margin-right: 1px;
    background: #aab2bf;
    content: "";
}

.th-hero-support i {
    font-size: 1.2rem;
}

/* Hero product image */
.th-hero-product {
    position: relative;
    z-index: 2;
    display: flex;
    min-width: 0;
    align-items: center;
    justify-content: center;
    margin-right: -23px;
    margin-left: -27px;
}

.th-hero-product img {
    display: block;
    width: 125%;
    max-width: 860px;
    height: auto;
    object-fit: contain;
    filter: drop-shadow(0 15px 20px rgba(15, 23, 42, .12));
}

/* Carousel controls */
.th-hero-controls {
    position: absolute;
    z-index: 5;
    right: max(24px, calc((100% - 1280px) / 2));
    bottom: 28px;
    display: flex;
    align-items: center;
    gap: 14px;
}

.th-hero-indicators {
    display: flex;
    align-items: center;
    gap: 7px;
}

.th-hero-indicator {
    width: 10px;
    height: 10px;
    padding: 0;
    border: 0;
    border-radius: 50%;
    background: #d8dee5;
    cursor: pointer;
    transition: width .2s ease, background-color .2s ease;
}

.th-hero-indicator.is-active {
    width: 11px;
    height: 11px;
    background: var(--th-green);
}

/* Keyboard accessibility */
.th-hero-button:focus-visible,
.th-hero-indicator:focus-visible {
    outline: 3px solid rgba(22, 163, 74, .38);
    outline-offset: 3px;
}

/* Medium screens and smaller laptops */
@media (max-width: 1050px) {
    .th-home-hero {
        min-height: 570px;
    }

    .th-home-hero::before {
        right: -16%;
        width: 63%;
    }

    .th-hero-stage {
        width: min(100% - 64px, 900px);
        min-height: 570px;
        grid-template-columns: minmax(0, .98fr) minmax(0, 1.02fr);
    }

    .th-hero-title {
        font-size: clamp(2.4rem, 4.8vw, 3.8rem);
    }

    .th-hero-actions {
        gap: 10px;
    }

    .th-hero-button {
        padding-right: 14px;
        padding-left: 14px;
        font-size: .94rem;
    }

    .th-hero-button-primary,
    .th-hero-button-secondary {
        min-width: 0;
    }

    .th-hero-product {
        margin-left: -35px;
    }

    .th-hero-product img {
        width: 100%;
        max-width: 590px;
    }
}

/* Smaller tablets */
@media (min-width: 821px) and (max-width: 1024px) {
    .th-hero-title {
        font-size: clamp(2.4rem, 4.1vw, 2.8rem);
    }

    .th-hero-button {
        min-height: 48px;
        gap: 8px;
        padding-right: 11px;
        padding-left: 11px;
        font-size: .86rem;
    }
}

/* Medium desktop image positioning */
@media (min-width: 1051px) and (max-width: 1480px) {
    .th-hero-product {
        transform: translateX(clamp(-65px, calc(50vw - 736px), 0px));
    }
}

/* Tablet and mobile */
@media (max-width: 820px) {
    .th-home-hero,
    .th-hero-carousel-inner {
        min-height: 0;
    }

    .th-home-hero {
        padding: 0;
    }

    .th-hero-carousel-inner {
        padding-bottom: calc(64px + env(safe-area-inset-bottom));
    }

    /* Green background shape */
    .th-home-hero::before {
        top: 0;
        right: -42%;
        bottom: 30px;
        width: 82%;
        height: auto;
        border-radius: 58% 0 0 45% / 100% 0 0 100%;
    }

    .th-home-hero::after {
        top: auto;
        right: 31%;
        bottom: 22%;
        width: 30%;
        border-width: 12px;
    }

    .th-hero-dots {
        top: 8%;
        right: 5%;
        width: 72px;
        height: 48px;
    }

    /* Keep text and image in one horizontal composition */
    .th-hero-stage {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(0, .8fr);
        align-items: center;
        gap: 0;
        width: min(100% - 48px, 740px);
        min-height: clamp(360px, 55vw, 460px);
        margin: 0 auto;
        padding: 24px 0 0;
    }

    .th-hero-copy {
        max-width: none;
        min-width: 0;
        padding: 0;
        text-align: left;
    }

    .th-hero-eyebrow {
        gap: 7px;
        margin-bottom: 10px;
        padding: 7px 10px;
        font-size: .78rem;
    }

    .th-hero-title {
        margin-bottom: 9px;
        font-size: clamp(1.75rem, 4.3vw, 2.55rem);
        letter-spacing: -.045em;
        line-height: 1.02;
    }

    .th-hero-description {
        max-width: 32rem;
        margin: 0 0 15px;
        font-size: clamp(.88rem, 1.55vw, 1.05rem);
        line-height: 1.42;
    }

    .th-hero-actions {
        justify-content: flex-start;
        gap: 8px;
    }

    .th-hero-button {
        min-height: 46px;
        gap: 8px;
        padding-right: 12px;
        padding-left: 12px;
        border-radius: 14px;
        font-size: .84rem;
    }

    .th-hero-support {
        justify-content: flex-start;
        gap: 8px;
        margin-top: 14px;
        font-size: .83rem;
    }

    /*
     * One continuous image sizing formula from 320px to 820px.
     * Do not override the width or transform at 540px.
     */
    .th-hero-product {
        width: clamp(320px, calc(64.1vw + 115px), 560px);
        max-width: none;
        min-width: 0;
        justify-self: end;
        margin: 0;
        transform: translateX(clamp(50px, calc(6.8vw + 29px), 85px));
        transform-origin: center center;
    }

    .th-hero-product img {
        display: block;
        width: 100%;
        max-width: none;
        height: auto;
        object-fit: contain;
    }

    /* Carousel controls */
    .th-hero-controls {
        right: 16px;
        bottom: calc(12px + env(safe-area-inset-bottom));
        gap: 9px;
        transform: none;
    }

    /* WhatsApp positioning */
    body:has(.th-home-hero) a[title="Chat with us on WhatsApp"] {
        right: 16px !important;
        bottom: calc(74px + env(safe-area-inset-bottom)) !important;
        width: 44px !important;
        height: 44px !important;
    }
}

/* Small phones */
@media (max-width: 540px) {
    .th-hero-stage {
        grid-template-columns: minmax(0, 1.2fr) minmax(0, .8fr);
        width: min(100% - 36px, 460px);
        min-height: 0;
        padding-top: 20px;
    }

    .th-hero-eyebrow {
        gap: 6px;
        margin-bottom: 8px;
        padding: 6px 8px;
        font-size: .7rem;
    }

    .th-hero-title {
        margin-bottom: 8px;
        font-size: clamp(1.28rem, 6.1vw, 1.78rem);
    }

    .th-hero-description {
        margin-bottom: 11px;
        font-size: clamp(.74rem, 3vw, .87rem);
        line-height: 1.38;
    }

    .th-hero-actions {
        flex-direction: column;
        align-items: flex-start;
        gap: 7px;
    }

    .th-hero-button {
        width: min(100%, 205px);
        min-height: 38px;
        gap: 6px;
        padding-right: 8px;
        padding-left: 8px;
        border-radius: 12px;
        font-size: clamp(.65rem, 2.6vw, .76rem);
    }

    .th-hero-support {
        gap: 6px;
        margin-top: 11px;
        font-size: .7rem;
    }

    /*
     * Intentionally no product width or transform override here.
     * The shared 820px rule keeps image scaling continuous.
     */
    .th-hero-controls {
        right: 10px;
        bottom: calc(10px + env(safe-area-inset-bottom));
        gap: 7px;
    }

    .th-home-hero::before {
        right: -52%;
        bottom: 0;
        width: 84%;
    }

    .th-home-hero .th-hero-indicator {
        background-color: rgba(255, 255, 255, .62);
    }

    .th-home-hero .th-hero-indicator.is-active {
        width: 11px;
        height: 11px;
        background-color: #fff;
    }
}

/* Respect reduced-motion preferences */
@media (prefers-reduced-motion: reduce) {
    .th-hero-slide,
    .th-hero-slide-text,
    .th-hero-button,
    .th-hero-indicator {
        transition: none;
    }
}

</style>

<script>
(() => {
    const carousel = document.getElementById('heroCarousel');
    if (!carousel) return;

    const slides = Array.from(carousel.querySelectorAll('.th-hero-slide'));
    const indicators = Array.from(carousel.querySelectorAll('[data-hero-indicator]'));
    if (slides.length < 2) return;

    let activeIndex = slides.findIndex((slide) => slide.classList.contains('is-active'));
    if (activeIndex < 0) activeIndex = 0;
    let timer;
    const slideInterval = 5500;
    let nextChangeAt = Date.now() + slideInterval;

    const showSlide = (index) => {
        activeIndex = (index + slides.length) % slides.length;
        slides.forEach((slide, slideIndex) => {
            const active = slideIndex === activeIndex;
            slide.classList.toggle('is-active', active);
            slide.setAttribute('aria-hidden', String(!active));
        });
        indicators.forEach((indicator, indicatorIndex) => {
            const active = indicatorIndex === activeIndex;
            indicator.classList.toggle('is-active', active);
            indicator.setAttribute('aria-current', String(active));
        });
    };

    const advanceDueSlides = () => {
        const now = Date.now();
        if (now < nextChangeAt) return;

        const elapsedIntervals = Math.floor((now - nextChangeAt) / slideInterval) + 1;
        showSlide(activeIndex + elapsedIntervals);
        nextChangeAt += elapsedIntervals * slideInterval;
    };

    const scheduleNext = () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => {
            advanceDueSlides();
            scheduleNext();
        }, Math.max(0, nextChangeAt - Date.now()));
    };

    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', () => showSlide(index));
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            advanceDueSlides();
            scheduleNext();
        }
    });

    scheduleNext();
})();
</script>

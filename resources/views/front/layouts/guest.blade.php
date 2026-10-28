<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet" />

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

        <!-- Bootstrap Icons -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

        <!-- Font Awesome Icons -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

        <!-- Vite Assets (Tailwind + App JS) -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Livewire Styles -->
        @livewireStyles
    </head>
    <body class="font-sans text-gray-900 antialiased">

        @include('front.partials.header')

        <main>
            @yield('content')
        </main>

        @include('front.partials.footer')

        <!-- Bootstrap Bundle JS (includes Popper) -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

        <!-- Livewire Scripts -->
        @livewireScripts

        {{-- ── WhatsApp Floating Button ── --}}
        <a href="https://wa.me/078690891?text={{ urlencode('Hello TRESCA, I want to talk about the product you are selling') }}"
           target="_blank"
           rel="noopener noreferrer"
           title="Chat with us on WhatsApp"
           style="
               position: fixed;
               bottom: 28px;
               right: 24px;
               z-index: 9999;
               width: 56px;
               height: 56px;
               border-radius: 50%;
               background: #25D366;
               display: flex;
               align-items: center;
               justify-content: center;
               box-shadow: 0 4px 20px rgba(37,211,102,0.45);
               text-decoration: none;
               transition: transform 0.2s ease, box-shadow 0.2s ease;
           "
           onmouseover="this.style.transform='scale(1.1)';this.style.boxShadow='0 6px 28px rgba(37,211,102,0.6)';"
           onmouseout="this.style.transform='scale(1)';this.style.boxShadow='0 4px 20px rgba(37,211,102,0.45)';">
            <i class="fab fa-whatsapp" style="font-size: 1.75rem; color: #fff;"></i>
        </a>

        <style>
.hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to right, rgba(20,26,36,.88) 0%, rgba(20,26,36,.60) 50%, rgba(20,26,36,.15) 100%);
}
.carousel-caption-custom {
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    transform: translateY(-50%);
    z-index: 10;
    padding: 0 1.5rem;
    max-width: 1200px;
    margin: 0 auto;
    text-align: left;
}
@media (min-width: 768px) {
    section.position-relative { height: 600px !important; }
}
.hero-dot {
    width: 8px !important;
    height: 8px !important;
    border-radius: 100px !important;
    background-color: rgba(255,255,255,.5) !important;
    border: none !important;
    transition: width .4s ease, background-color .4s ease !important;
}
.hero-dot.active {
    width: 32px !important;
    background-color: hsl(var(--accent)) !important;
}
.service-store-card {
    border-color: hsl(var(--border));
    background: hsl(var(--card));
    box-shadow: var(--shadow-card);
    transition: all .3s ease;
}
.service-store-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-card-hover) !important;
}
.service-store-card:hover .store-img { transform: scale(1.1); }
#servicesScrollContainer::-webkit-scrollbar { display: none; }
.product-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-card-hover) !important; }
.product-card:hover .product-img { transform: scale(1.06); }

/* Mobile: move WhatsApp button above bottom nav */
@media (max-width: 1199px) {
    a[href*="wa.me"] {
        bottom: 80px !important;
    }
}
        </style>

        <script>
function scrollServices(amount) {
    const el = document.getElementById('servicesScrollContainer');
    if (el) el.scrollBy({ left: amount, behavior: 'smooth' });
}
document.addEventListener('DOMContentLoaded', function () {
    const carousel = document.getElementById('heroCarousel');
    if (!carousel) return;
    carousel.addEventListener('slide.bs.carousel', function (e) {
        document.querySelectorAll('.hero-dot').forEach((d, i) => {
            d.classList.toggle('active', i === e.to);
        });
    });
});
        </script>

    </body>
</html>
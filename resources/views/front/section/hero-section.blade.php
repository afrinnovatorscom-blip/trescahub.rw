@php
    $heroSlides = \App\Models\Background::all();
@endphp

<!-- Hero Slider Section -->
<section class="position-relative overflow-hidden" style="height: 500px; min-height: 500px;">

    @if($heroSlides->isEmpty())
        <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-dark">
            <p class="text-white">No slides available.</p>
        </div>
    @else
        <!-- Manual Carousel (No Bootstrap dependency) -->
        <div id="heroCarousel" class="hero-carousel w-100 h-100">
            
            <!-- Slides Container -->
            <div class="hero-carousel-inner h-100">
                @foreach($heroSlides as $index => $slide)

                    @php
                        // Resolve the CTA URL
                        $goToVal = $slide->go_to ?? null;
                        $goTo = route('shop');

                        if (!empty($goToVal)) {
                            $val = trim($goToVal);
                            if (str_starts_with($val, 'http://') || str_starts_with($val, 'https://')) {
                                $goTo = $val;
                            } else {
                                try {
                                    $goTo = route($val);
                                } catch (\Exception $e) {
                                    $goTo = route('shop');
                                }
                            }
                        }

                        // Slide fields
                        $title = $slide->title ?? '';
                        $desc = $slide->description ?? null;
                        $thumbnail = $slide->thumbnail ?? null;
                        $buttonText = $slide->button_text ?? 'Learn More';

                        // Split title for last word highlight
                        $words = explode(' ', trim($title));
                        $lastWord = array_pop($words);
                    @endphp

                    <div class="hero-carousel-item h-100 {{ $index === 0 ? 'active' : '' }}" data-index="{{ $index }}">
                        <!-- Background Image -->
                        @if($thumbnail)
                        <div class="h-100 w-100 position-absolute" 
                             style="background-image: url('{{ asset('' . $thumbnail) }}'); 
                                    background-size: cover; 
                                    background-position: center;">
                        </div>
                        @endif

                        <!-- Gradient Overlay -->
                        <div class="position-absolute top-0 start-0 w-100 h-100"
                             style="background: linear-gradient(to right, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.6) 50%, rgba(0,0,0,0.3) 100%);">
                        </div>

                        <!-- Content -->
                        <div class="position-absolute top-50 start-0 translate-middle-y w-100">
                            <div class="container">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <!-- Powered By -->
                                        <p class="text-uppercase small fw-bold mb-3" 
                                           style="color: #F67723; letter-spacing: 2px; font-size: 0.875rem;">
                                            POWERED BY TRESCA GROUP
                                        </p>

                                        <!-- Title -->
                                        <h1 class="fw-bold text-white mb-3" 
                                            style="font-size: clamp(2rem, 5vw, 3.5rem); line-height: 1.2;">
                                            {{ implode(' ', $words) }} 
                                            <span style="color: #F67723;">{{ $lastWord }}</span>
                                        </h1>

                                        <!-- Description -->
                                        @if($desc)
                                            <p class="text-white-50 mb-4" style="font-size: 1.1rem; max-width: 500px;">
                                                {{ $desc }}
                                            </p>
                                        @endif

                                        <!-- CTA Button -->
                                        <a href="{{ $goTo }}" 
                                           class="btn btn-lg d-inline-flex align-items-center" 
                                           style="background-color: #F67723; 
                                                  color: white; 
                                                  padding: 12px 35px; 
                                                  border-radius: 5px; 
                                                  font-weight: 600;
                                                  border: none;
                                                  transition: all 0.3s ease;
                                                  text-decoration: none;">
                                            {{ $buttonText }}
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" class="ms-2">
                                                <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Indicators/Dots -->
            @if($heroSlides->count() > 1)
            <div class="hero-carousel-indicators">
                @foreach($heroSlides as $index => $slide)
                <button type="button" 
                        class="hero-indicator {{ $index === 0 ? 'active' : '' }}" 
                        data-slide="{{ $index }}"
                        aria-label="Slide {{ $index + 1 }}">
                </button>
                @endforeach
            </div>

            <!-- Controls/Arrows -->
            <button class="hero-carousel-control hero-carousel-control-prev" id="heroPrev" type="button">
                <span class="hero-carousel-control-icon" aria-hidden="true">‹</span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="hero-carousel-control hero-carousel-control-next" id="heroNext" type="button">
                <span class="hero-carousel-control-icon" aria-hidden="true">›</span>
                <span class="visually-hidden">Next</span>
            </button>
            @endif

        </div>
    @endif
</section>

<style>
/* Height settings */
.hero-carousel,
.hero-carousel-inner,
.hero-carousel-item {
    height: 500px;
    position: relative;
}

@media (min-width: 768px) {
    .hero-carousel,
    .hero-carousel-inner,
    .hero-carousel-item {
        height: 600px;
    }
}

/* Slide transitions */
.hero-carousel-inner {
    position: relative;
    width: 100%;
    overflow: hidden;
}

.hero-carousel-item {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.6s ease-in-out, visibility 0.6s ease-in-out;
}

.hero-carousel-item.active {
    opacity: 1;
    visibility: visible;
    z-index: 10;
}

/* Indicators styling */
.hero-carousel-indicators {
    position: absolute;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 20;
    display: flex;
    gap: 8px;
}

.hero-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: rgba(255, 255, 255, 0.5);
    border: none;
    padding: 0;
    cursor: pointer;
    transition: all 0.3s ease;
}

.hero-indicator.active {
    background-color: #F67723;
    transform: scale(1.2);
}

.hero-indicator:hover {
    background-color: #F67723;
}

/* Control arrows */
.hero-carousel-control {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 50px;
    height: 50px;
    border: none;
    border-radius: 50%;
    background-color: rgba(0, 0, 0, 0.3);
    color: white;
    font-size: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 20;
    transition: all 0.3s ease;
    opacity: 0;
}

.hero-carousel:hover .hero-carousel-control {
    opacity: 1;
}

.hero-carousel-control-prev {
    left: 20px;
}

.hero-carousel-control-next {
    right: 20px;
}

.hero-carousel-control:hover {
    background-color: #F67723;
}

.hero-carousel-control-icon {
    line-height: 1;
    margin-top: -5px;
}

/* Button hover effect */
.btn:hover {
    background-color: #e55a0f !important;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(246, 119, 35, 0.3);
}

/* Text utility */
.text-white-50 {
    color: rgba(255, 255, 255, 0.8) !important;
}

/* Mobile adjustments */
@media (max-width: 768px) {
    .hero-carousel-control {
        display: none !important;
    }
    
    .hero-carousel-indicators {
        bottom: 20px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manual carousel implementation
    const carousel = {
        slides: document.querySelectorAll('.hero-carousel-item'),
        indicators: document.querySelectorAll('.hero-indicator'),
        prevBtn: document.getElementById('heroPrev'),
        nextBtn: document.getElementById('heroNext'),
        currentIndex: 0,
        interval: null,
        slideCount: {{ $heroSlides->count() }},
        
        init: function() {
            if (this.slideCount <= 1) return;
            
            // Show first slide
            this.showSlide(0);
            
            // Add click events to indicators
            this.indicators.forEach((indicator, index) => {
                indicator.addEventListener('click', () => {
                    this.showSlide(index);
                    this.resetInterval();
                });
            });
            
            // Add click events to prev/next buttons
            if (this.prevBtn) {
                this.prevBtn.addEventListener('click', () => {
                    this.prevSlide();
                    this.resetInterval();
                });
            }
            
            if (this.nextBtn) {
                this.nextBtn.addEventListener('click', () => {
                    this.nextSlide();
                    this.resetInterval();
                });
            }
            
            // Start auto-sliding
            this.startInterval();
        },
        
        showSlide: function(index) {
            // Hide all slides
            this.slides.forEach(slide => {
                slide.classList.remove('active');
            });
            
            // Deactivate all indicators
            this.indicators.forEach(indicator => {
                indicator.classList.remove('active');
            });
            
            // Show selected slide
            this.slides[index].classList.add('active');
            
            // Activate corresponding indicator
            if (this.indicators[index]) {
                this.indicators[index].classList.add('active');
            }
            
            this.currentIndex = index;
        },
        
        nextSlide: function() {
            let nextIndex = this.currentIndex + 1;
            if (nextIndex >= this.slideCount) {
                nextIndex = 0;
            }
            this.showSlide(nextIndex);
        },
        
        prevSlide: function() {
            let prevIndex = this.currentIndex - 1;
            if (prevIndex < 0) {
                prevIndex = this.slideCount - 1;
            }
            this.showSlide(prevIndex);
        },
        
        startInterval: function() {
            this.interval = setInterval(() => {
                this.nextSlide();
            }, 5000); // Change slide every 5 seconds
        },
        
        resetInterval: function() {
            clearInterval(this.interval);
            this.startInterval();
        }
    };
    
    // Initialize carousel
    carousel.init();
    
    // Pause on hover
    const carouselElement = document.querySelector('.hero-carousel');
    if (carouselElement) {
        carouselElement.addEventListener('mouseenter', () => {
            clearInterval(carousel.interval);
        });
        
        carouselElement.addEventListener('mouseleave', () => {
            carousel.startInterval();
        });
    }
    
    console.log('Hero carousel initialized with {{ $heroSlides->count() }} slides');
});
</script>
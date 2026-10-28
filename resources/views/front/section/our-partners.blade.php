<section class="py-5 py-md-6 border-top" aria-labelledby="our-partners-heading">
    <div class="container-xl px-4 px-lg-5">
        <div class="mx-auto mb-5 text-center" style="max-width: 640px;">
            <h2 id="our-partners-heading" class="fw-bold mb-3"
                style="font-family:'Space Grotesk',sans-serif;font-size:clamp(1.75rem,4vw,2.5rem);">
                Our Partners
            </h2>
            <p class="text-muted mb-0"
               style="font-family:'DM Sans',sans-serif;">
                Working together with businesses that help us deliver more value.
            </p>
        </div>

        @if($partners->isNotEmpty())
            <div class="row justify-content-center g-4 g-md-5">
                @foreach($partners as $partner)
                    @php
                        $logoPath = preg_replace('#^(public/)?assets/#', '', ltrim((string) $partner->business_logo_path, '/')) ?? '';
                        $logoExists = $logoPath !== ''
                            && ! str_contains($logoPath, '..')
                            && \Illuminate\Support\Facades\File::isFile(public_path('assets/'.$logoPath));
                        $initial = \Illuminate\Support\Str::upper(
                            \Illuminate\Support\Str::substr(trim($partner->business_name), 0, 1)
                        );
                    @endphp
                    <div class="col-6 col-sm-4 col-lg-3">
                        <div class="d-flex h-100 flex-column align-items-center text-center">
                            <div class="d-flex align-items-center justify-content-center mb-3 rounded-3"
                                 style="width:100%;height:112px;background-color:hsl(var(--muted));">
                                @if($logoExists)
                                    <img src="{{ asset('assets/'.$logoPath) }}"
                                         alt="{{ $partner->business_name }}"
                                         loading="lazy"
                                         style="max-width:80%;max-height:80%;object-fit:contain;"
                                         onerror="this.onerror=null; this.hidden=true; this.nextElementSibling.style.display='flex';">
                                @endif
                                <span class="align-items-center justify-content-center rounded-circle fw-bold text-white"
                                      aria-hidden="true"
                                      style="display:{{ $logoExists ? 'none' : 'flex' }};width:56px;height:56px;background-color:#2d8c5e;font-family:'Space Grotesk',sans-serif;font-size:1.5rem;">
                                    {{ $initial }}
                                </span>
                            </div>
                            <h3 class="fw-semibold mb-0"
                                style="font-family:'DM Sans',sans-serif;font-size:1rem;line-height:1.4;overflow-wrap:anywhere;">
                                {{ $partner->business_name }}
                            </h3>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-center text-muted mb-0">
                Partner logos will appear here as businesses are approved for public display.
            </p>
        @endif
    </div>
</section>

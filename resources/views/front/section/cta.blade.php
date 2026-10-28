@if(isset($ctas) && $ctas->isNotEmpty())
<section style="padding: 64px 0; background: #f9f8f4;">
    <div style="max-width:1200px; margin:0 auto; padding:0 24px;">
        <div class="d-flex flex-wrap gap-4">

            @foreach($ctas as $cta)
                @if($cta->items && count($cta->items) > 0)
                    @foreach($cta->items as $index => $item)

                    @php
                        $itemTitle = strtolower($item['title'] ?? '');
                        if (str_contains($itemTitle, 'youth') || str_contains($itemTitle, 'student') || str_contains($itemTitle, 'young')) {
                            $ctaEmoji = '🎓';
                        } elseif (str_contains($itemTitle, 'partner') || str_contains($itemTitle, 'client') || str_contains($itemTitle, 'business')) {
                            $ctaEmoji = '🤝';
                        } elseif (str_contains($itemTitle, 'shop') || str_contains($itemTitle, 'product') || str_contains($itemTitle, 'store')) {
                            $ctaEmoji = '🛒';
                        } elseif (str_contains($itemTitle, 'service') || str_contains($itemTitle, 'engineer')) {
                            $ctaEmoji = '⚙️';
                        } elseif (str_contains($itemTitle, 'design') || str_contains($itemTitle, 'creative')) {
                            $ctaEmoji = '🎨';
                        } elseif (str_contains($itemTitle, 'event')) {
                            $ctaEmoji = '🎉';
                        } else {
                            $ctaEmoji = optional($cta->category)->icon ?? '🏷️';
                        }
                    @endphp

                    <div class="d-flex flex-column"
                         style="
                             flex: 1 1 300px;
                             background: #ffffff;
                             border: 1px solid #e8e6e0;
                             border-radius: 16px;
                             padding: 36px 32px;
                             gap: 14px;
                             box-shadow: 0 2px 8px rgba(0,0,0,0.04);
                             transition: box-shadow 0.2s, transform 0.2s;
                         "
                         onmouseover="this.style.boxShadow='0 8px 28px rgba(0,0,0,0.09)'; this.style.transform='translateY(-2px)'"
                         onmouseout="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.04)'; this.style.transform='translateY(0)'">

                        {{-- Emoji --}}
                        <div style="font-size: 2.2rem; line-height: 1; margin-bottom: 4px;">
                            {{ $ctaEmoji }}
                        </div>

                        {{-- Title --}}
                        @if(!empty($item['title']))
                            <h3 style="font-size:1.15rem; font-weight:700; color:#111827; margin:0; line-height:1.3;">
                                {{ $item['title'] }}
                            </h3>
                        @endif

                        {{-- Description --}}
                        @if(!empty($item['description']))
                            <p style="font-size:0.9rem; color:#6b7280; line-height:1.7; margin:0; flex:1;">
                                {{ $item['description'] }}
                            </p>
                        @endif

                        {{-- Learn More button → goes to cta public show --}}
                        <div style="margin-top: 8px;">
                            <a href="{{ route('ctas.show', $cta->id) }}"
                               style="
                                   display:inline-flex; align-items:center; gap:6px;
                                   padding:9px 22px; border-radius:8px;
                                   background:#ffffff; color:#111827;
                                   font-weight:600; font-size:0.875rem;
                                   text-decoration:none;
                                   border:1.5px solid #d1d5db;
                                   transition: background 0.18s, color 0.18s, border-color 0.18s;
                               "
                               onmouseover="this.style.background='#f97316'; this.style.color='#ffffff'; this.style.borderColor='#f97316'"
                               onmouseout="this.style.background='#ffffff'; this.style.color='#111827'; this.style.borderColor='#d1d5db'">
                                Learn More
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            </a>
                        </div>

                    </div>

                    @endforeach
                @endif
            @endforeach

        </div>
    </div>
</section>
@endif
@props(['sectionKey'])
@php
    $definition = config('website.sections')[$sectionKey] ?? null;
    $section = $website->section($sectionKey);
    $variant = $definition['variant'] ?? 'text';
@endphp
@if($section['_visible'])
    @if($variant === 'strip')
        <div class="capabilities-strip"><div class="container"><span>{{ $section['eyebrow'] }}</span><x-media-image :id="$section['image_id'] ?? null" image-class="strip-image"/><div>@foreach($section['items'] as $item){{ $item['title'] }}@unless($loop->last)<i>/</i>@endunless @endforeach</div></div></div>
    @else
        <section @if($sectionKey === 'home.expertise' || $variant === 'services') id="expertise" @endif @class(['section', 'why-section' => $variant === 'principles', 'process-section' => $variant === 'process', 'home-process' => $sectionKey === 'home.process', 'home-featured-work' => $sectionKey === 'home.portfolio', 'cta-section' => $variant === 'cta', 'company-story' => $variant === 'prose' && $definition['scope'] === 'about'])>
            <div @class(['container', 'why-grid' => $variant === 'principles', 'cta-inner' => $variant === 'cta', 'prose' => $variant === 'prose'])>
                @if($variant !== 'prose')
                    <div @class(['section-heading' => !in_array($variant,['principles','cta'])])>
                        <div>@if(!empty($section['eyebrow']))<p class="eyebrow">{{ $section['eyebrow'] }}</p>@endif @if(!empty($section['title']))<h2 class="multiline-heading">{{ $section['title'] }}</h2>@endif
                        @if(in_array($variant,['principles','cta']) && !empty($section['description']))<p>{{ $section['description'] }}</p>@endif
                        @if(in_array($variant,['principles']) && !empty($section['button_label']))<a class="text-link" href="{{ $website->url($section['button_url']) }}">{{ $section['button_label'] }} @if(preg_match('~^https?://~i', $section['button_url'] ?? ''))<span aria-hidden="true">&nearr;</span>@endif</a>@endif
                        <x-media-image :id="$section['image_id'] ?? null" image-class="section-image"/>
                        </div>
                        @if(!in_array($variant,['principles','cta']))<div>@if(!empty($section['description']))<p>{{ $section['description'] }}</p>@endif @if(!empty($section['button_label']))<a class="text-link" href="{{ $website->url($section['button_url']) }}">{{ $section['button_label'] }} @if(preg_match('~^https?://~i', $section['button_url'] ?? ''))<span aria-hidden="true">&nearr;</span>@endif</a>@endif</div>@endif
                    </div>
                @endif
                @if($variant === 'services')
                    <x-services/>
                @elseif($variant === 'projects')
                    <x-projects :homepage-only="$definition['scope'] === 'home'"/>
                @elseif($variant === 'cta')
                    @if(!empty($section['button_label']))
                        <div class="button-row cta-actions">
                            <a class="button" href="{{ $website->url($section['button_url']) }}">{{ $section['button_label'] }}</a>
                            @php($bookingUrl = config('howvanta.contact_links.Book a Call'))
                            @if(is_string($bookingUrl) && str_starts_with($bookingUrl, 'https://') && filter_var($bookingUrl, FILTER_VALIDATE_URL))
                                <a class="button button-ghost" href="{{ $bookingUrl }}" data-contact-action="booking">Book a Call <span aria-hidden="true">&nearr;</span></a>
                            @elseif(request()->routeIs('portfolio', 'portfolio.show'))
                                <a class="button button-ghost" href="{{ route('services') }}">Explore Services</a>
                            @else
                                <a class="button button-ghost" href="{{ route('portfolio') }}">View Case Studies</a>
                            @endif
                        </div>
                    @endif
                @elseif($variant === 'principles')
                    <div class="principles">@foreach($section['items'] as $item)<div><span>{{ str_pad($loop->iteration,2,'0',STR_PAD_LEFT) }}</span><div><h3>{{ $item['title'] }}</h3><p>{{ $item['text'] ?? '' }}</p>@if(!empty($item['url']))<a class="text-link" href="{{ $website->url($item['url']) }}">{{ $item['title'] }} @if(preg_match('~^https?://~i', $item['url'] ?? ''))<span aria-hidden="true">&nearr;</span>@endif</a>@endif</div></div>@endforeach</div>
                @elseif($variant === 'process')
                    <div class="process-grid">
                        @foreach($section['items'] as $item)
                            <div>
                                @if($sectionKey === 'home.process')
                                    <div class="process-card-heading">
                                        <span class="process-num">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                        <x-process-icon :step="$item['title']"/>
                                    </div>
                                @else
                                    <span class="process-num">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }} <i>&rarr;</i></span>
                                @endif
                                <h3>{{ $item['title'] }}</h3>
                                <p>{{ $item['text'] ?? '' }}</p>
                                @if(!empty($item['url']))
                                    <a class="text-link" href="{{ $website->url($item['url']) }}">{{ $item['title'] }} @if(preg_match('~^https?://~i', $item['url'] ?? ''))<span aria-hidden="true">&nearr;</span>@endif</a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @elseif($variant === 'cards')
                    <div @class(['engagement-grid', 'expertise-grid' => $sectionKey === 'home.expertise'])>
                        @foreach($section['items'] as $item)
                            <article>
                                @if($sectionKey === 'home.expertise')
                                    <div class="expertise-art" aria-hidden="true">
                                        <span class="expertise-symbol"><i></i><i></i><i></i><i></i></span>
                                    </div>
                                @endif
                                <div class="expertise-card-copy">
                                    <h3>{{ $item['title'] }}</h3>
                                    <p>{{ $item['text'] ?? '' }}</p>
                                    @if(!empty($item['url']))
                                        <a class="text-link" href="{{ $website->url($item['url']) }}">{{ $item['title'] }} @if(preg_match('~^https?://~i', $item['url'] ?? ''))<span aria-hidden="true">&nearr;</span>@endif</a>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @elseif($variant === 'prose')
                    <x-media-image :id="$section['image_id'] ?? null" image-class="section-image"/>
                    @foreach($section['items'] as $item)<h2 id="{{ \Illuminate\Support\Str::slug(str_replace('.', '-', $sectionKey).'-'.$item['title']) }}">{{ $item['title'] }}</h2><p class="editorial-content">{{ $item['text'] ?? '' }}</p>@if(!empty($item['url']))<a class="text-link" href="{{ $website->url($item['url']) }}">{{ $item['title'] }} @if(preg_match('~^https?://~i', $item['url'] ?? ''))<span aria-hidden="true">&nearr;</span>@endif</a>@endif @endforeach
                @endif
            </div>
        </section>
    @endif
@endif

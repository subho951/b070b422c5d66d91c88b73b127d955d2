<x-layout :title="$pageContent->title" :description="$pageContent->description">
@php
    $hero = $website->section('home.hero');
@endphp
@if($hero['_visible'])
    @php
        $defaultSlides = config('website.sections')['home.expertise']['fields']['items']['default'];
        $slides = array_replace($defaultSlides, array_slice($website->section('home.expertise')['items'], 0, 3));
        $slideImages = ['software', 'applications', 'support'];
        $serviceTitles = array_column($defaultSlides, 'title');
        $heroImage = $pageContent->featured_image_id ?? $hero['image_id'];
    @endphp
    <section class="hero home-hero-carousel" aria-label="Howvanta services" aria-roledescription="carousel" data-hero-carousel>
        <div class="hero-grid" aria-hidden="true"></div>
        <h1 class="sr-only">{{ $pageContent->title }}</h1>
        <div class="container home-hero-stage">
            @foreach($slides as $slide)
                @php($imageIndex = array_search($slide['title'], $serviceTitles, true))
                <div id="hero-slide-{{ $loop->iteration }}" class="hero-content home-hero-slide"
                    role="group" aria-roledescription="slide" aria-label="{{ $loop->iteration }} of {{ $loop->count }}"
                    aria-hidden="{{ $loop->first ? 'false' : 'true' }}" @if(!$loop->first) inert @endif data-hero-slide>
                    <div class="hero-copy">
                        <p class="eyebrow"><span class="status-dot"></span>{{ $pageContent->eyebrow ?? $hero['eyebrow'] }}</p>
                        <h2 class="home-hero-title">{{ $slide['title'] }}</h2>
                        <p class="hero-description">{{ $slide['text'] ?? '' }}</p>
                        <div class="button-row">
                            @foreach($hero['items'] as $item)
                                <a @class(['button', 'button-ghost' => !$loop->first]) href="{{ $website->url($item['url']) }}">{{ $item['title'] }}</a>
                            @endforeach
                            @php($bookingUrl = config('howvanta.contact_links.Book a Call'))
                            @if(is_string($bookingUrl) && str_starts_with($bookingUrl, 'https://') && filter_var($bookingUrl, FILTER_VALIDATE_URL))
                                <a class="button button-ghost" href="{{ $bookingUrl }}" data-contact-action="booking">Book a Call <span aria-hidden="true">&nearr;</span></a>
                            @endif
                        </div>
                        @if(!empty($hero['note']))
                            <div class="hero-note"><span class="note-rule"></span><span>{{ $hero['note'] }}</span></div>
                        @endif
                    </div>
                    <div class="home-hero-visual" aria-hidden="true">
                        <div class="home-hero-image home-hero-image--{{ $slideImages[$imageIndex === false ? $loop->index : $imageIndex] }}">
                            @if($loop->first && $heroImage)
                                <x-media-image :id="$heroImage" loading="eager"/>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="container home-hero-controls" data-hero-controls hidden>
            <div class="home-hero-options" role="group" aria-label="Choose a service slide">
                @foreach($slides as $slide)
                    <button type="button" aria-controls="hero-slide-{{ $loop->iteration }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" data-hero-select>{{ $slide['title'] }}</button>
                @endforeach
            </div>
            <div class="home-hero-arrows">
                <button type="button" aria-label="Previous service slide" data-hero-previous>&larr;</button>
                <button type="button" aria-label="Next service slide" data-hero-next>&rarr;</button>
            </div>
        </div>
        <p class="sr-only" role="status" data-hero-status></p>
        <div class="container hero-bottom">
            <span>{{ $hero['bottom_text'] }}</span>
            <a href="{{ $website->url($hero['discover_url']) }}">{{ $hero['discover_label'] }} <span aria-hidden="true">&darr;</span></a>
        </div>
    </section>
@endif
@if($pageContent->content)<section class="section"><div class="container prose editorial-content">{{ $pageContent->content }}</div></section>@endif
@foreach($website->sections('home') as $sectionRecord)
    @if(!in_array($sectionRecord->key, ['home.hero', 'home.capabilities'], true))<x-website-section :section-key="$sectionRecord->key"/>@endif
    @if($sectionRecord->key === 'home.expertise')
        <section class="section section-tight" aria-labelledby="home-experience">
            <div class="container audit-proof">
                <div><p class="eyebrow">Founder’s experience</p><h2 id="home-experience">15+ years in software engineering</h2><a class="text-link" href="{{ route('founder') }}">Meet Subhomoy Samanta</a></div>
                <div><h3>Work across industries</h3><p>Explore school administration, professional networking, media, retail operations, and business websites in our portfolio.</p><a class="text-link" href="{{ route('portfolio') }}">See the projects</a></div>
            </div>
        </section>
    @endif
@endforeach
<x-testimonial-slider/>
<x-cta/>
</x-layout>

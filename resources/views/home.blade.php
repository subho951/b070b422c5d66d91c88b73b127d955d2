<x-layout :title="$pageContent->title" :description="$pageContent->description">
@php($hero = $website->section('home.hero'))
@if($hero['_visible'])
<section class="hero"><div class="hero-grid" aria-hidden="true"></div><div class="container hero-content"><div class="hero-copy"><p class="eyebrow"><span class="status-dot"></span>{{ $pageContent->eyebrow ?? $hero['eyebrow'] }}</p><h1>{{ $pageContent->title }}</h1><p class="hero-description">{{ $pageContent->description }}</p><div class="button-row">@foreach($hero['items'] as $item)<a @class(['button', 'button-ghost' => !$loop->first]) href="{{ $website->url($item['url']) }}">{{ $item['title'] }} <span aria-hidden="true">&nearr;</span></a>@endforeach</div><div class="hero-note"><span class="note-rule"></span><span>{{ $hero['note'] }}</span></div></div>
@if($pageContent->featured_image_id || $hero['image_id'])<div class="hero-uploaded-image"><x-media-image :id="$pageContent->featured_image_id ?? $hero['image_id']" loading="eager"/></div>@else<div class="hero-product-scene" aria-label="Our approach to connected product development">
    <div class="product-scene-top"><span class="product-scene-dot"></span><span>IDEAS INTO WORKING SOFTWARE</span><span aria-hidden="true">&nearr;</span></div>
    <div class="product-scene-heading"><span>Built around your business.</span><h2>One vision.<br>Connected delivery.</h2></div>
    <div class="product-workspace"><div class="workspace-top"><span>YOUR NEXT PRODUCT</span><span aria-hidden="true">&bull; &bull; &bull;</span></div><div class="workspace-body"><div class="workspace-track"><span>Discovery</span><span>Design</span><span>Development</span><span>Launch &amp; support</span></div><div class="workspace-preview"><div class="preview-block preview-blue"><span>Web platforms</span><strong>Business, connected.</strong><div class="preview-bars" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></div></div><div class="preview-modules"><div><span aria-hidden="true">&#9678;</span>Mobile experiences</div><div><span aria-hidden="true">&#10035;</span>AI &amp; automation</div></div></div></div></div>
    <div class="product-scene-bottom"><span>Strategy meets engineering.</span><span>Built to evolve <span aria-hidden="true">&nearr;</span></span></div>
</div>@endif</div><div class="container hero-bottom"><span>{{ $hero['bottom_text'] }}</span><a href="{{ $website->url($hero['discover_url']) }}">{{ $hero['discover_label'] }} <span aria-hidden="true">&darr;</span></a></div></section>
@endif
@if($pageContent->content)<section class="section"><div class="container prose editorial-content">{{ $pageContent->content }}</div></section>@endif
@foreach($website->sections('home') as $sectionRecord)
    @if($sectionRecord->key !== 'home.hero')<x-website-section :section-key="$sectionRecord->key"/>@endif
@endforeach
<x-cta/>
</x-layout>

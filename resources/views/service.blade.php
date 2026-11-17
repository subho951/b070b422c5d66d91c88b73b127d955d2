<x-layout :title="$title" :description="$description">
    <section class="page-hero container service-detail-hero">
        <a class="text-link" href="{{ route('services') }}">&larr; {{ $website->branding('service_back_label') }}</a>
        <p class="eyebrow">{{ $website->branding('service_eyebrow') }}</p>
        <h1>{{ $title }}</h1><p>{{ $description }}</p>
        <a class="button" href="{{ route('contact') }}">{{ $website->branding('service_button_label') }} &nearr;</a>
        <x-media-image :id="$record->featured_image_id" image-class="service-detail-cover" loading="eager"/>
    </section>
    @if($record->content)
        <section class="section section-tight"><div class="container service-detail-copy"><p class="eyebrow">BUILT AROUND YOUR BUSINESS</p><h2>What we do</h2><div class="service-paragraphs">
            @foreach(preg_split('/\R\s*\R/', trim($record->content)) as $paragraph)
                <p data-service-paragraph>{{ $paragraph }}</p>
            @endforeach
        </div></div></section>
    @endif
    <x-website-section section-key="service.approach"/>
    <x-cta/>
</x-layout>

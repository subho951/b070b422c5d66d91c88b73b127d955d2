@php
    $serviceImage = $website->image($record->featured_image_id);
    $description = $guide['outcome'] ?? $description;
    $quoteUrl = route('contact', ['intent' => 'quote', 'service' => $record->slug]).'#enquiry';
@endphp
<x-layout  :title="$record->seo_title ?: ($guide['seo_title'] ?? $title.' Services')" :description="$record->seo_description ?: $description" :share-image="$serviceImage ? route('media.show', $serviceImage->id) : null">
    <section class="page-hero container service-detail-hero">
        <nav class="audit-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span aria-hidden="true">/</span><a href="{{ route('services') }}">Services</a><span aria-hidden="true">/</span><span aria-current="page">{{ $title }}</span></nav>
        <p class="eyebrow">Services</p>
        <h1>{{ $title }}</h1><p>{{ $description }}</p>
        <a class="button" href="{{ $quoteUrl }}">{{ $website->branding('service_button_label') }}</a>
        @if($proof = $relatedProjects->first())
            <p class="service-example"><a class="text-link" href="{{ route('portfolio.show', $proof->slug) }}">See the {{ $proof->title }} case study</a></p>
        @endif
        <x-media-image :id="$record->featured_image_id" image-class="service-detail-cover" loading="eager"/>
    </section>
    @if($record->content || $guide)
        <section class="section section-tight">
            <div class="container service-detail-copy">
                <h2>What we do</h2>
                <div class="service-paragraphs">
                    @foreach(preg_split('/\R\s*\R/', trim((string) $record->content), -1, PREG_SPLIT_NO_EMPTY) as $paragraph)
                        <p data-service-paragraph>{{ $paragraph }}</p>
                    @endforeach
                </div>
                @if($guide)
                    <h3>When this helps</h3>
                    <ul class="audit-list">@foreach($guide['uses'] as $use)<li>{{ $use }}</li>@endforeach</ul>
                @endif
            </div>
        </section>
    @endif
    @if($guide)
        <section class="section section-tight">
            <div class="container audit-service-columns">
                <article class="audit-panel"><h2>What you receive</h2><ul class="audit-list">@foreach($guide['deliverables'] as $deliverable)<li>{{ $deliverable }}</li>@endforeach</ul><h3>Technology</h3><p>{{ $guide['technology'] }}</p></article>
                <article class="audit-panel"><h2>How the work runs</h2><ol class="audit-list">@foreach($guide['process'] as $step)<li>{{ $step }}</li>@endforeach</ol><p><a class="text-link" href="{{ route('engagement') }}">Compare engagement models</a></p></article>
            </div>
        </section>
    @endif
    @if($relatedProjects->isNotEmpty())
        <section class="section section-tight">
            <div class="container"><h2>Related work</h2><x-projects :projects="$relatedProjects"/></div>
        </section>
    @endif
    @if($guide)
        <section class="section section-tight">
            <div class="container audit-faq">
                <h2>Frequently asked questions</h2>
                @foreach($guide['faq'] + [
                    'How long will the work take?' => 'After reviewing your requirements, we propose a schedule with milestones and the information we need from your team.',
                    'How is pricing agreed?' => 'The quote describes the scope, deliverables and payment terms. Ongoing support can be discussed separately.',
                ] as $question => $answer)
                    <details><summary>{{ $question }}</summary><p>{{ $answer }}</p></details>
                @endforeach
            </div>
        </section>
    @endif
    <section class="section cta-section">
        <div class="container cta-inner">
            <div><h2>Discuss {{ $title }}</h2><p>Tell us what you need and we’ll discuss what to include in your quote.</p></div>
            <a class="button" href="{{ $quoteUrl }}">{{ $website->branding('service_button_label') }}</a>
        </div>
    </section>
</x-layout>

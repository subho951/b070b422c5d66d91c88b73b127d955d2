@php($labels = $website->section('project.labels'))
@php($projectUrl = config('featured-projects.'.$record->slug.'.url'))

@php($projectImage = $website->image($record->featured_image_id))
<x-layout :title="$record->seo_title ?: ($title.' Case Study')" :description="$record->seo_description ?: $record->description" :share-image="$projectImage ? route('media.show', $projectImage->id) : null">
    <section class="page-hero project-detail-hero">
        <div class="container">
            <a class="text-link" href="{{ route('portfolio') }}">&larr; {{ $website->branding('project_back_label') }}</a>
            <p class="eyebrow">{{ $record->is_demonstration ? $labels['demonstration_label'] : $labels['project_label'] }} &middot; {{ $record->category }}</p>
            <h1>{{ $title }}</h1>
            <p>{{ $record->description }}</p>
            <x-media-image :id="$record->featured_image_id ?? $labels['image_id']" image-class="page-featured-image" loading="eager"/>
            @if($projectUrl)
                <a class="button button-small" href="{{ $projectUrl }}" target="_blank" rel="noopener noreferrer">Visit project website <span aria-hidden="true">&nearr;</span></a>
            @endif
            @if($record->is_demonstration)
                <p class="project-disclaimer">{{ $labels['disclaimer'] }}</p>
            @endif
        </div>
    </section>
    <section class="section">
        <div class="container prose">
            @if($record->challenge)
                <h2>{{ $labels['challenge_heading'] }}</h2>
                <p class="editorial-content">{{ $record->challenge }}</p>
            @endif
            @if($record->content)
                @if($projectUrl && !$record->is_demonstration)
                    <h2>Howvanta’s role</h2>
                @endif
                <div class="editorial-content">{{ $record->content }}</div>
            @endif
            @if($record->solution)
                <h2>{{ $labels['solution_heading'] }}</h2>
                <p class="editorial-content">{{ $record->solution }}</p>
            @endif
            @if($record->outcome)
                <h2>{{ $labels['outcome_heading'] }}</h2>
                <p class="editorial-content">{{ $record->outcome }}</p>
            @endif
            @if(!$record->content && !$record->challenge && !$record->solution && !$record->outcome)
                <h2>{{ $labels['fallback_title'] }}</h2>
                <p>{{ $labels['fallback_description'] }}</p>
            @endif
        </div>
    </section>
    @if($relatedService || $previousProject || $nextProject)
        <nav class="container audit-project-links" aria-label="Explore related work">
            @if($relatedService)<a class="text-link" href="{{ route('services.show', $relatedService->slug) }}">Related service: {{ $relatedService->title }}</a>@endif
            @if($previousProject)<a class="text-link" href="{{ route('portfolio.show', $previousProject->slug) }}">Previous: {{ $previousProject->title }}</a>@endif
            @if($nextProject)<a class="text-link" href="{{ route('portfolio.show', $nextProject->slug) }}">Next: {{ $nextProject->title }}</a>@endif
        </nav>
    @endif
    @if($projectUrl && !$record->is_demonstration)
        <section class="section cta-section">
            <div class="container cta-inner">
                <h2>Have a similar project in mind?</h2>
                <a class="button" href="{{ route('contact', ['intent' => 'consultation'] + ($relatedService ? ['service' => $relatedService->slug] : [])) }}#enquiry">Discuss a Similar Project</a>
            </div>
        </section>
    @else
        <x-cta/>
    @endif
</x-layout>

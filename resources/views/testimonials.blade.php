<x-layout :title="$pageContent->title" :description="$pageContent->description">
    <x-page-intro :page-content="$pageContent" label="CLIENT FEEDBACK" class="testimonials-intro"/>
    <section class="section testimonial-feedback-section" aria-labelledby="client-feedback-title">
        <div class="container">
            @if($websiteTestimonials->isNotEmpty())
                <h2 class="sr-only" id="client-feedback-title">What our clients say</h2>
                <div class="testimonial-grid">
                    @foreach($websiteTestimonials as $testimonial)
                        <x-testimonial-card :testimonial="$testimonial"/>
                    @endforeach
                </div>
            @else
                @php($emptyState = $website->section('testimonials.empty'))
                @if($emptyState['_visible'])
                    <div class="testimonial-empty">
                        <span class="eyebrow">{{ $emptyState['eyebrow'] }}</span>
                        <h2 class="multiline-heading" id="client-feedback-title">{{ $emptyState['title'] }}</h2>
                        <p>{{ $emptyState['description'] }}</p>
                        <x-media-image :id="$emptyState['image_id']" image-class="section-image"/>
                        <a class="button" href="{{ $website->url($emptyState['button_url']) }}">{{ $emptyState['button_label'] }}</a>
                    </div>
                @endif
            @endif
        </div>
    </section>
    <x-cta/>
</x-layout>

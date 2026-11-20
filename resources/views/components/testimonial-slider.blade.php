@if($websiteTestimonials->isNotEmpty())
    <section class="section home-testimonials" aria-labelledby="home-testimonials-title" data-testimonial-slider>
        <div class="container testimonial-showcase">
            <div class="testimonial-showcase-intro">
                <p class="eyebrow">CLIENT FEEDBACK</p>
                <h2 id="home-testimonials-title">What our clients say</h2>
                <a class="button button-ghost" href="{{ route('testimonials') }}">View all testimonials</a>
            </div>
            <div class="testimonial-showcase-content">
                <div class="testimonial-track" id="home-testimonial-track" data-testimonial-track tabindex="0" aria-label="Client testimonial slides">
                    @foreach($websiteTestimonials as $testimonial)
                        <x-testimonial-card :testimonial="$testimonial"/>
                    @endforeach
                </div>
                @if($websiteTestimonials->count() > 1)
                    <div class="testimonial-controls" data-testimonial-controls hidden>
                        <button type="button" aria-label="Previous testimonial" aria-controls="home-testimonial-track" data-testimonial-prev>&larr;</button>
                        <span role="status" data-testimonial-index>1 / {{ $websiteTestimonials->count() }}</span>
                        <button type="button" aria-label="Next testimonial" aria-controls="home-testimonial-track" data-testimonial-next>&rarr;</button>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endif

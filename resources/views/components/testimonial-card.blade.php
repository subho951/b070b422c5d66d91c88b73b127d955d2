@props(['testimonial'])
<article class="quote-card" aria-label="Testimonial from {{ $testimonial->name }}">
    <div class="testimonial-quote" style="--testimonial-hue: {{ fmod($testimonial->id * 137.508, 360) }}">
        <div class="testimonial-card-top">
            <span class="testimonial-quote-mark" aria-hidden="true">&ldquo;</span>
            @if($testimonial->rating)
                <div class="testimonial-rating">
                    <span class="sr-only">{{ $testimonial->rating }} out of 5 stars</span>
                    <span aria-hidden="true">{{ str_repeat('★', (int) $testimonial->rating) }}{{ str_repeat('☆', 5 - (int) $testimonial->rating) }}</span>
                </div>
            @endif
        </div>
        <blockquote>{{ $testimonial->description }}</blockquote>
    </div>
    <footer class="testimonial-card-meta">
        <p class="testimonial-review-title">{{ $testimonial->title }}</p>
        <div class="testimonial-attribution">
            <div class="quote-person">
                @if($testimonial->featured_image_id)
                    <x-media-image :id="$testimonial->featured_image_id" image-class="testimonial-photo"/>
                @else
                    <span class="person-avatar" aria-hidden="true">{{ mb_substr($testimonial->name, 0, 1) }}</span>
                @endif
                <div>
                    <strong>{{ $testimonial->name }}</strong>
                    @if($testimonial->company)<p>{{ $testimonial->company }}</p>@endif
                    @if($testimonial->service_label)<p>{{ $testimonial->service_label }}</p>@endif
                </div>
            </div>
            @if($testimonial->reviewed_on)
                <time datetime="{{ $testimonial->reviewed_on }}">{{ \Illuminate\Support\Carbon::parse($testimonial->reviewed_on)->format('j M Y') }}</time>
            @endif
        </div>
    </footer>
</article>

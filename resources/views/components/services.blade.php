<div class="service-grid">
    @forelse($websiteServices as $service)
        <a class="service-card" href="{{ route('services.show', $service->slug) }}">
            <x-media-image :id="$service->featured_image_id" image-class="service-cover"/>
            <div class="service-card-body"><h3>{{ $service->title }}</h3><p>{{ $service->description }}</p><span class="text-link">Explore service <span aria-hidden="true">&nearr;</span></span></div>
        </a>
    @empty
        <p class="empty-state">{{ $website->section('shared.empty_states')['services_message'] }} <a href="{{ route('contact') }}">{{ $website->section('shared.empty_states')['services_link_label'] }}</a></p>
    @endforelse
</div>

@props(['grouped' => false])
@php
    $groups = [
        'Build digital products' => ['custom-software', 'web-applications', 'mobile-apps', 'saas-products'],
        'Intelligence and cloud' => ['ai-applications', 'cloud-engineering'],
        'Keep systems running' => ['hosting-domains', 'software-testing', 'dedicated-teams'],
    ];
    $sections = collect(['' => $websiteServices]);
    if ($grouped && $websiteServices->isNotEmpty()) {
        $sections = collect($groups)->map(fn (array $slugs) => $websiteServices->whereIn('slug', $slugs))->filter(fn ($services) => $services->isNotEmpty());
        $remaining = $websiteServices->whereNotIn('slug', collect($groups)->flatten());
        if ($remaining->isNotEmpty()) {
            $sections->put('Other services', $remaining);
        }
    }
@endphp
@foreach($sections as $heading => $services)
    @if($heading !== '')<h2 class="services-group-heading">{{ $heading }}</h2>@endif
    <div class="service-grid">
        @forelse($services as $service)
            <a class="service-card" href="{{ route('services.show', $service->slug) }}">
                <x-media-image :id="$service->featured_image_id" image-class="service-cover"/>
                <div class="service-card-body">
                    <h3>{{ $service->title }}</h3>
                    <p>{{ $service->description }}</p>
                    <span class="text-link service-card-action">{{ $website->branding('service_explore_label') }}</span>
                </div>
            </a>
        @empty
            <p class="empty-state">{{ $website->section('shared.empty_states')['services_message'] }} <a href="{{ route('contact') }}">{{ $website->section('shared.empty_states')['services_link_label'] }}</a></p>
        @endforelse
    </div>
@endforeach

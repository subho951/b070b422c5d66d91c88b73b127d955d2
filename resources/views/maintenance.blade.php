@php
    $maintenanceCopy = $website->section('maintenance.screen');
    $contactEmail = $siteSettings['contact_email'] ?? 'info@howvanta.com';
    $contactPhone = $siteSettings['contact_phone'] ?? '+91 62893 39520';
    $phoneLink = preg_replace('/[^+0-9]/', '', $contactPhone);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $maintenanceCopy['title'] }} | {{ $website->branding('site_name') }}</title>
    <link rel="icon" href="{{ $website->logo('favicon') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="maintenance-body maintenance-refresh">
    <main class="maintenance-shell">
        <section class="maintenance-main" aria-labelledby="maintenance-title">
            <div class="maintenance-brand"><img src="{{ $website->logo('light') }}" alt="{{ $website->branding('site_name') }}" width="280" height="82"></div>
            <span class="maintenance-status"><span aria-hidden="true"></span>Website maintenance</span>
            <p class="maintenance-kicker">{{ $maintenanceCopy['eyebrow'] }}</p>
            <h1 id="maintenance-title">{{ $maintenanceCopy['title'] }}</h1>
            <div class="maintenance-message">{!! \Illuminate\Support\Str::markdown((string) $message, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}</div>
            <x-media-image :id="$maintenanceCopy['image_id']" image-class="maintenance-image"/>
            <div class="maintenance-actions"><a class="button" href="mailto:{{ $contactEmail }}">{{ $maintenanceCopy['button_label'] }} <span aria-hidden="true">&nearr;</span></a>@if($phoneLink)<a class="maintenance-call" href="tel:{{ $phoneLink }}">Call our team <span aria-hidden="true">&nearr;</span></a>@endif</div>
        </section>
        <aside class="maintenance-aside" aria-label="Contact our team">
            <div class="maintenance-illustration" aria-hidden="true"><svg viewBox="0 0 200 160" fill="none"><rect x="25" y="30" width="150" height="100" rx="12" fill="#152e50" stroke="#52749f"/><path d="M25 56h150" stroke="#52749f"/><circle cx="40" cy="43" r="3" fill="#22d3ee"/><circle cx="51" cy="43" r="3" fill="#60a5fa"/><circle cx="62" cy="43" r="3" fill="#52749f"/><path d="m77 76-14 14 14 14m46-28 14 14-14 14m-17-34-12 40" stroke="#7bd9f0" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><rect x="127" y="114" width="52" height="28" rx="14" fill="#2563eb"/><path d="m143 128 6 6 13-13" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
            <p class="maintenance-aside-label">THE CONVERSATION CONTINUES</p>
            <h2>Still here to help.</h2>
            <p>Have a question or a project in mind? You can reach our team while we work on the website.</p>
            <div class="maintenance-contact"><span>Email us</span><a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a></div>
            @if($phoneLink)<div class="maintenance-contact"><span>Call us</span><a href="tel:{{ $phoneLink }}">{{ $contactPhone }}</a></div>@endif
            <div class="maintenance-thanks">Thank you for your patience.<br>We look forward to connecting with you.</div>
        </aside>
    </main>
    <footer class="maintenance-footer">&copy; {{ date('Y') }} {{ $website->branding('site_name') }} <span aria-hidden="true">&middot;</span> {{ $website->branding('tagline') }}</footer>
</body>
</html>

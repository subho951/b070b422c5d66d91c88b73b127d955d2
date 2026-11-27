@php
    $measurementId = config('services.ga4.measurement_id');
    $measurementId = is_string($measurementId) && preg_match('/^G-[A-Z0-9]+$/D', $measurementId) ? $measurementId : '';
    $consent = session('analytics_consent');
@endphp
<details class="analytics-preferences" data-analytics-preferences
    data-url="{{ route('analytics.consent') }}" data-token="{{ csrf_token() }}"
    data-consent="{{ $consent === null ? 'unset' : ($consent ? 'granted' : 'denied') }}"
    data-measurement-id="{{ $measurementId }}" data-debug="{{ config('services.ga4.debug') ? 'true' : 'false' }}"
    data-lead-source="{{ session('lead_attribution.last.source') }}"
    data-landing-source="{{ request()->attributes->get('landing_source') }}" hidden>
    <summary>Analytics preferences</summary>
    <p>Allow optional analytics and save how you found us with any enquiry you send. <a href="{{ route('privacy') }}">Privacy policy</a></p>
    <button class="button button-small" type="button" data-analytics-choice="granted">Allow analytics</button>
    <button class="button button-small" type="button" data-analytics-choice="denied">Decline analytics</button>
    <p role="status" aria-live="polite" data-analytics-status></p>
</details>

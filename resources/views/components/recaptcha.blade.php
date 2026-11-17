@if(\App\Support\Recaptcha::isEnabled(request()) && config('services.recaptcha.site_key'))
<meta name="recaptcha-site-key" content="{{ config('services.recaptcha.site_key') }}">
<script src="https://www.google.com/recaptcha/api.js?render={{ rawurlencode(config('services.recaptcha.site_key')) }}" async defer></script>
@endif

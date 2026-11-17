@props(['action', 'enabled', 'label', 'field' => 'visible', 'setting' => 'Visibility', 'onLabel' => 'On', 'offLabel' => 'Off'])
<form method="post" action="{{ $action }}" class="visibility-toggle-form">
    @csrf
    @method('PATCH')
    <input type="hidden" name="{{ $field }}" value="{{ $enabled ? '0' : '1' }}">
    <button type="submit" class="visibility-toggle" role="switch" aria-checked="{{ $enabled ? 'true' : 'false' }}" aria-label="{{ $setting.' for '.$label }}" title="{{ ($enabled ? 'Turn off ' : 'Turn on ').strtolower($setting) }}">
        <span class="visibility-toggle-track" aria-hidden="true"><span></span></span>
        <span class="visibility-toggle-label" aria-hidden="true">{{ $enabled ? $onLabel : $offLabel }}</span>
    </button>
</form>

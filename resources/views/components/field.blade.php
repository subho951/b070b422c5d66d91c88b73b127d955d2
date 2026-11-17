@props(['name', 'label', 'type' => 'text', 'required' => false, 'maxlength' => 255])

<div class="field">
    <label for="{{ $name }}">
        {{ $label }}
        @if($required)
            <span aria-hidden="true">*</span>
        @else
            <small>(optional)</small>
        @endif
    </label>

    <div @class(['password-input' => $type === 'password'])>
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ $type === 'password' ? '' : old($name) }}"
            maxlength="{{ $maxlength }}"
            @required($required)
            @if($errors->has($name))
                aria-invalid="true"
                aria-describedby="{{ $name }}-error"
            @endif
            {{ $attributes }}
        >

        @if($type === 'password')
            <button
                class="password-toggle"
                type="button"
                data-password-toggle
                aria-label="Show password"
                aria-controls="{{ $name }}"
                aria-pressed="false"
                hidden
            >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/>
                    <circle cx="12" cy="12" r="3"/>
                    <path class="password-eye-slash" d="m3 3 18 18"/>
                </svg>
            </button>
        @endif
    </div>

    @error($name)
        <span class="field-error" id="{{ $name }}-error">{{ $message }}</span>
    @enderror
</div>

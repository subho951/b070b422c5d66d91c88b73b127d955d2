@props(['name' => 'featured_image_id', 'selected' => null, 'label' => 'Featured image'])
@php
    $fieldId = preg_replace('/[^a-zA-Z0-9_-]/', '-', $name);
    $asset = is_scalar($selected) && $selected ? \Illuminate\Support\Facades\DB::table('media_assets')->find($selected) : null;
@endphp
<div class="field media-picker" data-media-field>
    <label for="{{ $fieldId }}-choose">{{ $label }}</label>
    <input type="hidden" id="{{ $fieldId }}" name="{{ $name }}" value="{{ $asset?->id }}" data-media-value>
    <div class="media-field-control">
        <div class="media-field-thumbnail" data-media-field-thumbnail @if(!$asset) hidden @endif>
            <img @if($asset) src="{{ route('media.show',$asset->id) }}" @endif alt="{{ $asset?->alt_text }}" data-media-field-image>
            <span class="media-image-fallback" hidden>Image preview unavailable</span>
        </div>
        <div class="media-field-description"><strong data-media-field-name>{{ $asset ? ($asset->title ?: $asset->original_name) : 'No image selected' }}</strong><span>Choose from your library or upload a new image.</span><div class="media-field-actions"><button id="{{ $fieldId }}-choose" type="button" class="button button-secondary" data-media-open aria-haspopup="dialog" aria-controls="admin-media-dialog"><x-admin-icon name="plus"/>{{ $asset ? 'Change image' : 'Choose image' }}</button><button type="button" class="text-button danger-link" data-media-clear @if(!$asset) hidden @endif>Remove</button></div></div>
    </div>
    <noscript><small>Enable JavaScript to choose or upload an image.</small></noscript>
</div>

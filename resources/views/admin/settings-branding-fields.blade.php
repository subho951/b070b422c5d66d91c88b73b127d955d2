@foreach(['light' => 'Header logo (light background)', 'dark' => 'Footer and admin logo (dark background)', 'favicon' => 'Browser icon'] as $variant => $label)
<x-media-picker :name="'logo_'.$variant" :selected="old('logo_'.$variant, $brandingSettings->get('branding.logo_'.$variant)?->media_asset_id)" :label="$label"/>
@endforeach
<div class="settings-fields">@foreach(config('website.branding') as $key => $default)
<div class="field"><label for="{{ $key }}">{{ ucfirst(str_replace('_', ' ', $key)) }}</label><input name="{{ $key }}" id="{{ $key }}" value="{{ old($key, $brandingSettings->get('branding.'.$key)?->value ?? $default) }}" maxlength="1000" @required(!str_ends_with($key,'_url'))></div>
@endforeach
</div>

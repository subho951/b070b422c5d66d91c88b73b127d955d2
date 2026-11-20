<x-admin-layout :title="$definition['label']">
<a class="text-link" href="{{ route('admin.sections.index') }}">&larr; All sections</a><div class="editor-heading"><p class="eyebrow">{{ strtoupper($definition['scope']) }}</p><h1>{{ $definition['label'] }}</h1><p>Text is safely displayed as plain text. Use line breaks in headings. Local links start with /, such as /contact.</p></div>
<form class="form-panel content-form" method="post" action="{{ route('admin.sections.update', $record->id) }}">
@csrf @method('PUT')
@foreach($definition['fields'] as $key => $field)
    @php($value = old('data.'.$key, $data[$key] ?? $field['default']))
    @if($field['type'] === 'image')
        <x-media-picker :name="'data['.$key.']'" :selected="$value" :label="$field['label']"/>
    @elseif($field['type'] === 'items')
        @php($entries = is_array($value) ? array_values($value) : [])
        <div class="field" data-repeater data-next-index="{{ count($entries) }}"><h2>{{ $field['label'] }}</h2><p>Add, remove or move items to change their display order. Up to 40 items.</p><input type="hidden" name="data[items]" value=""><div data-repeater-items>@foreach($entries as $index => $item)<x-section-item :index="$index" :item="$item" :menu="$definition['variant'] === 'menu'"/>@endforeach</div><template data-repeater-template><x-section-item index="__INDEX__" :menu="$definition['variant'] === 'menu'"/></template><button class="button button-small" type="button" data-item-add>Add item +</button></div>
    @else
        <div class="field"><label for="data-{{ $key }}">{{ $field['label'] }}</label><textarea name="data[{{ $key }}]" id="data-{{ $key }}" rows="{{ $field['type'] === 'textarea' ? 4 : 2 }}">{{ is_scalar($value) ? $value : '' }}</textarea></div>
    @endif
@endforeach
@if(in_array($definition['variant'], ['labels', 'screen']))<input type="hidden" name="is_visible" value="1"><input type="hidden" name="sort_order" value="{{ $record->sort_order }}">@else<div class="form-grid"><div class="field"><label for="is_visible">Visibility</label><select id="is_visible" name="is_visible"><option value="1" @selected(old('is_visible',$record->is_visible) == 1)>Visible</option><option value="0" @selected(old('is_visible',$record->is_visible) == 0)>Hidden</option></select></div><div class="field"><label for="sort_order">Display order</label><input id="sort_order" type="number" name="sort_order" min="0" max="9999" value="{{ old('sort_order',$record->sort_order) }}" required></div></div>
@endif<button class="button" type="submit">Save section &rarr;</button>
</form></x-admin-layout>

@props(['index', 'item' => [], 'menu' => false])
<div class="repeater-item" data-repeater-item>
    <div class="repeater-actions"><span>Item</span><button type="button" data-item-up aria-label="Move item up">Move up</button><button type="button" data-item-down aria-label="Move item down">Move down</button><button type="button" data-item-remove>Remove</button></div>
    <div class="field"><label>Title / label<input name="data[items][{{ $index }}][title]" value="{{ $item['title'] ?? '' }}" maxlength="255" required></label></div>
    @if(!$menu)<div class="field"><label>Description<textarea name="data[items][{{ $index }}][text]" rows="3">{{ $item['text'] ?? '' }}</textarea></label></div>@endif
    <div class="field"><label>Link (optional)<input name="data[items][{{ $index }}][url]" value="{{ $item['url'] ?? '' }}" placeholder="/contact or https://example.com"></label></div>
</div>

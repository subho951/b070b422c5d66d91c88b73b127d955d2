@props(['id' => null, 'imageClass' => '', 'loading' => 'lazy'])
@php($image = $website->image($id ? (int) $id : null))
@if($image)
    <img src="{{ route('media.show', $image->id) }}" alt="{{ $image->alt_text }}" @if($image->title) title="{{ $image->title }}" @endif width="{{ $image->width }}" height="{{ $image->height }}" loading="{{ $loading }}" class="{{ $imageClass }}">
@endif

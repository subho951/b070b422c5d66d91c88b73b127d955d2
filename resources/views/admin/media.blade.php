<x-admin-layout title="Media library">
    <p class="eyebrow">WEBSITE IMAGES</p><h1>Your media library.</h1><p>Upload once, reuse across pages, sections, projects, services, testimonials and logos. Images currently in use cannot be deleted.</p>
    <form class="form-panel media-upload" method="post" enctype="multipart/form-data" action="{{ route('admin.media.store') }}">
        @csrf
        <div class="media-upload-row">
            <div class="field"><label for="images">Image files</label><input id="images" type="file" name="images[]" multiple accept="image/*" required></div>
            <div class="field"><label for="title">Title <small>(optional)</small></label><input id="title" name="title" maxlength="255" value="{{ old('title') }}"></div>
            <div class="field"><label for="alt_text">Alt text <small>(optional)</small></label><input id="alt_text" name="alt_text" maxlength="255" value="{{ old('alt_text') }}"></div>
            <button class="button" type="submit">Upload images &uarr;</button>
        </div>
        <p class="form-footnote media-upload-help">All image formats. Up to 20 files per upload. Maximum 8 MB per image. Some formats may not support browser previews. Optional title and alt text apply to all selected images and can be edited individually later.</p>
    </form>
    <form id="media-bulk-delete" class="media-bulk-toolbar" method="post" action="{{ route('admin.media.bulk-destroy') }}" data-confirm="Permanently delete the selected unused images? Images currently in use will be kept.">
        @csrf @method('DELETE')
        <label class="media-select-all"><input type="checkbox" data-media-select-all> Select all on this page</label><span data-media-selected-count role="status" aria-live="polite">0 selected</span><button class="button media-delete-button" type="submit" data-media-delete-selected disabled>Delete selected</button>
    </form>
    <div class="media-library-grid">
        @forelse($assets as $asset)
            <article class="media-library-card">
                <label class="media-card-select"><input type="checkbox" name="ids[]" value="{{ $asset->id }}" form="media-bulk-delete" data-media-select aria-label="Select {{ $asset->title ?: $asset->original_name }}"><span>Select image</span></label>
                <a href="{{ route('media.show', $asset->id) }}" target="_blank" rel="noopener"><img src="{{ route('media.show', $asset->id) }}" alt="{{ $asset->alt_text }}" loading="lazy" data-image-fallback><span class="media-image-fallback" hidden>Preview unavailable ? {{ $asset->mime_type }}</span></a>
                <div class="media-library-details"><strong>{{ $asset->title ?: $asset->original_name }}</strong><p>{{ $asset->original_name }}<br>@if($asset->width && $asset->height){{ $asset->width }} &times; {{ $asset->height }} &middot;@endif {{ number_format($asset->size_bytes / 1024) }} KB</p>
                    <form method="post" action="{{ route('admin.media.update', $asset->id) }}">@csrf @method('PATCH')<div class="field"><label for="title-{{ $asset->id }}">Title <small>(optional)</small></label><input id="title-{{ $asset->id }}" name="title" value="{{ $asset->title }}" maxlength="255"></div><div class="field"><label for="alt-{{ $asset->id }}">Alt text <small>(optional)</small></label><input id="alt-{{ $asset->id }}" name="alt_text" value="{{ $asset->alt_text }}" maxlength="255"></div><button class="text-button" type="submit">Save details</button></form>
                    <form method="post" action="{{ route('admin.media.destroy', $asset->id) }}" data-confirm="Delete this unused image permanently?">@csrf @method('DELETE')<button class="text-button danger-link" type="submit">Delete image</button></form>
                </div>
            </article>
        @empty
            <p class="empty-state">Your library is empty. Upload your first image above.</p>
        @endforelse
    </div>
    {{ $assets->links() }}
</x-admin-layout>

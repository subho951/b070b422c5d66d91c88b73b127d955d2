<dialog id="admin-media-dialog" class="media-dialog" data-media-dialog data-library-url="{{ route('admin.media.library') }}" data-upload-url="{{ route('admin.media.store') }}" aria-labelledby="media-dialog-title">
    <header class="media-dialog-header"><div><p class="eyebrow">IMAGE SELECTOR</p><h2 id="media-dialog-title">Choose an image</h2></div><button type="button" class="media-dialog-close" data-media-close aria-label="Close image selector">&times;</button></header>
    <div class="media-dialog-tabs" role="tablist" aria-label="Image source"><button type="button" id="media-library-tab" role="tab" aria-selected="true" aria-controls="media-library-panel" data-media-tab="library">Media library</button><button type="button" id="media-upload-tab" role="tab" aria-selected="false" aria-controls="media-upload-panel" tabindex="-1" data-media-tab="upload">Upload image</button></div>
    <div class="media-dialog-error error-message" role="alert" data-media-error hidden></div>
    <section id="media-library-panel" class="media-dialog-panel" role="tabpanel" aria-labelledby="media-library-tab">
        <div class="media-dialog-search field"><label class="sr-only" for="media-library-search">Search images</label><input type="search" id="media-library-search" placeholder="Search by title, filename or alt text..." maxlength="200" data-media-search></div>
        <p class="media-dialog-status" role="status" aria-live="polite" data-media-status></p>
        <div class="media-choice-grid" data-media-library></div>
        <div class="media-dialog-pagination"><button type="button" class="button button-secondary" data-media-prev>Previous</button><span data-media-page></span><button type="button" class="button button-secondary" data-media-next>Next</button></div>
    </section>
    <section id="media-upload-panel" class="media-dialog-panel" role="tabpanel" aria-labelledby="media-upload-tab" hidden>
        <form data-media-upload-form enctype="multipart/form-data" method="post" action="{{ route('admin.media.store') }}">
            @csrf
            <div class="media-upload-dropzone"><x-admin-icon name="plus"/><label for="media-picker-files">Choose images to upload</label><input type="file" id="media-picker-files" name="images[]" multiple accept="image/*" required><p>All image formats &middot; Maximum 8 MB per image &middot; Up to 20 images</p></div>
            <div class="form-grid"><div class="field"><label for="media-picker-title">Title <small>(optional)</small></label><input id="media-picker-title" name="title" maxlength="255"></div><div class="field"><label for="media-picker-alt">Alt text <small>(optional)</small></label><input id="media-picker-alt" name="alt_text" maxlength="255"></div></div>
            <p class="form-footnote">Images are added to the shared library. Choose ?Use selected image? to apply one to this field.</p><button type="submit" class="button" data-media-upload-button>Upload images</button><p role="status" aria-live="polite" data-media-upload-status></p>
        </form>
    </section>
    <footer class="media-dialog-footer"><span data-media-selected-label>No image selected</span><div><button type="button" class="button button-secondary" data-media-close>Cancel</button><button type="button" class="button" data-media-use disabled>Use selected image</button></div></footer>
</dialog>

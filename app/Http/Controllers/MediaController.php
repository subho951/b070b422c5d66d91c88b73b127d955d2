<?php

namespace App\Http\Controllers;

use App\Support\ImageUpload;
use App\Support\MediaUsage;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaController extends Controller
{
    public function index(): View
    {
        return view('admin.media', ['assets' => DB::table('media_assets')->latest('id')->paginate(24)]);
    }

    public function store(Request $request, ImageUpload $images): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'images' => ['required', 'array', 'min:1', 'max:20'],
            'images.*' => ['required', 'file', 'mimetypes:image/*', 'max:8192', function (string $attribute, mixed $file, Closure $fail) use ($images): void {
                if ($file instanceof UploadedFile && $file->isValid() && ($error = $images->validationError($file))) {
                    $fail($error);
                }
            }],
            'title' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);
        $paths = [];
        $assetIds = [];
        try {
            DB::transaction(function () use ($request, $data, $images, &$paths, &$assetIds): void {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('images', 'public');
                    abort_unless(is_string($path), 500, 'The images could not be saved.');
                    $paths[] = $path;
                    $dimensions = $images->dimensions($file);
                    $assetIds[] = DB::table('media_assets')->insertGetId([
                        'disk' => 'public', 'path' => $path,
                        'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
                        'title' => $data['title'] ?? null, 'alt_text' => $data['alt_text'] ?? null,
                        'mime_type' => $file->getMimeType(), 'size_bytes' => $file->getSize(),
                        'width' => $dimensions['width'], 'height' => $dimensions['height'],
                        'uploaded_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($paths);
            throw $exception;
        }

        if ($request->expectsJson()) {
            return response()->json(['data' => DB::table('media_assets')->whereIn('id', $assetIds)->get()->map(fn (object $asset): array => $this->assetData($asset))], 201);
        }

        return back()->with('success', count($paths).' image(s) uploaded. They are now available in every image selector.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless(DB::table('media_assets')->find($id), 404);
        $data = $request->validate(['title' => ['nullable', 'string', 'max:255'], 'alt_text' => ['nullable', 'string', 'max:255']]);
        DB::table('media_assets')->where('id', $id)->update($data + ['updated_at' => now()]);

        return back()->with('success', 'Image details updated.');
    }

    public function library(Request $request): JsonResponse
    {
        $data = $request->validate(['search' => ['nullable', 'string', 'max:200'], 'page' => ['nullable', 'integer', 'min:1']]);
        $query = DB::table('media_assets')->latest('id');
        if (! empty($data['search'])) {
            $query->where(function (Builder $query) use ($data): void {
                $query->where('title', 'like', '%'.$data['search'].'%')->orWhere('original_name', 'like', '%'.$data['search'].'%')->orWhere('alt_text', 'like', '%'.$data['search'].'%');
            });
        }
        $assets = $query->paginate(24);

        return response()->json(['data' => $assets->getCollection()->map(fn (object $asset): array => $this->assetData($asset)), 'current_page' => $assets->currentPage(), 'last_page' => $assets->lastPage(), 'total' => $assets->total()]);
    }

    /** @return array{id: int, url: string, name: string, title: ?string, alt_text: ?string, mime_type: string, width: ?int, height: ?int, size_bytes: int} */
    private function assetData(object $asset): array
    {
        return ['id' => (int) $asset->id, 'url' => route('media.show', $asset->id), 'name' => $asset->title ?: $asset->original_name, 'title' => $asset->title, 'alt_text' => $asset->alt_text, 'mime_type' => $asset->mime_type, 'width' => $asset->width, 'height' => $asset->height, 'size_bytes' => (int) $asset->size_bytes];
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless(DB::table('media_assets')->find($id), 404);
        $result = $this->deleteAssets([$id]);
        if ($result['protected'] > 0) {
            throw ValidationException::withMessages(['media' => 'This image is in use. Remove it from content, branding or business records before deleting it.']);
        }

        return back()->with('success', 'Unused image deleted.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:100'], 'ids.*' => ['required', 'integer', 'distinct', 'exists:media_assets,id']]);
        $result = $this->deleteAssets($data['ids']);
        $message = $result['deleted'].' image(s) deleted.';
        if ($result['protected'] > 0) {
            $message .= ' '.$result['protected'].' image(s) kept because they are currently in use.';
        }

        return back()->with('success', $message);
    }

    /** @param list<int|string> $ids
     * @return array{deleted: int, protected: int}
     */
    private function deleteAssets(array $ids): array
    {
        $staged = [];
        try {
            $result = DB::transaction(function () use ($ids, &$staged): array {
                $deleted = $protected = 0;
                foreach (DB::table('media_assets')->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get() as $asset) {
                    if (app(MediaUsage::class)->isInUse((int) $asset->id)) {
                        $protected++;

                        continue;
                    }
                    abort_unless($asset->disk === 'public' && str_starts_with($asset->path, 'images/') && ! str_contains($asset->path, '..'), 500, 'Invalid image storage path.');
                    $disk = Storage::disk('public');
                    if ($disk->exists($asset->path)) {
                        $temporary = 'images/.trash/'.Str::uuid().'-'.basename($asset->path);
                        abort_unless($disk->move($asset->path, $temporary), 500, 'The image could not be deleted.');
                        $staged[] = ['original' => $asset->path, 'temporary' => $temporary];
                    }
                    DB::table('media_assets')->where('id', $asset->id)->delete();
                    $deleted++;
                }

                return compact('deleted', 'protected');
            });
        } catch (\Throwable $exception) {
            foreach (array_reverse($staged) as $file) {
                Storage::disk('public')->move($file['temporary'], $file['original']);
            }
            throw $exception;
        }
        foreach ($staged as $file) {
            Storage::disk('public')->delete($file['temporary']);
        }

        return $result;
    }

    public function show(int $id): BinaryFileResponse
    {
        $asset = DB::table('media_assets')->find($id);
        abort_unless($asset && $asset->disk === 'public' && str_starts_with($asset->path, 'images/') && ! str_contains($asset->path, '..'), 404);
        abort_unless(Storage::disk('public')->exists($asset->path), 404);

        return response()->file(Storage::disk('public')->path($asset->path), ['Content-Type' => $asset->mime_type, 'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; img-src 'self' data:; sandbox", 'Cache-Control' => 'public, max-age=86400']);
    }
}

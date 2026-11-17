<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\WebsiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class MediaManagementTest extends TestCase
{
    use RefreshDatabase;

    private int $seededMediaCount = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(WebsiteSeeder::class);
        $this->seededMediaCount = DB::table('media_assets')->count();
        Storage::fake('public');
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        $user->role = 'administrator';
        $user->save();

        return $user;
    }

    /** @param array<string, mixed> $overrides */
    private function asset(array $overrides = []): object
    {
        $path = 'images/'.Str::uuid().'.png';
        Storage::disk('public')->put($path, 'fixture-image-content');
        $id = DB::table('media_assets')->insertGetId($overrides + ['disk' => 'public', 'path' => $path, 'original_name' => 'fixture.png', 'title' => null, 'alt_text' => null, 'mime_type' => 'image/png', 'size_bytes' => 100, 'width' => 100, 'height' => 60, 'created_at' => now(), 'updated_at' => now()]);

        return DB::table('media_assets')->find($id);
    }

    public function test_upload_supports_eight_megabytes_bmp_and_static_svg_and_returns_picker_metadata(): void
    {
        $this->actingAs($this->administrator());
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><style>.mark{fill:#fff}</style><defs><linearGradient id="paint"><stop offset="0" stop-color="#fff"/></linearGradient></defs><path fill="url(#paint)" d="M0 0h10v10z"/></svg>';
        $response = $this->postJson(route('admin.media.store'), ['images' => [UploadedFile::fake()->image('boundary.png')->size(8192), UploadedFile::fake()->image('bitmap.bmp'), UploadedFile::fake()->createWithContent('vector.svg', $svg)]]);
        $response->assertCreated()->assertJsonCount(3, 'data')->assertJsonStructure(['data' => [['id', 'url', 'name', 'mime_type', 'width', 'height', 'size_bytes']]]);
        $this->assertDatabaseCount('media_assets', $this->seededMediaCount + 3);
        $this->assertDatabaseHas('media_assets', ['original_name' => 'boundary.png', 'size_bytes' => 8192 * 1024]);
        $asset = DB::table('media_assets')->where('original_name', 'vector.svg')->first();
        $this->assertSame('image/svg+xml', $asset->mime_type);
        Storage::disk('public')->assertExists($asset->path);
        $this->get(route('media.show', $asset->id))->assertOk()->assertHeader('Content-Type', 'image/svg+xml')->assertHeader('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; img-src 'self' data:; sandbox");
    }

    public function test_oversized_or_nonimage_files_reject_the_entire_batch(): void
    {
        $this->actingAs($this->administrator());
        foreach ([UploadedFile::fake()->image('oversized.png')->size(8193), UploadedFile::fake()->createWithContent('document.txt', 'not an image'), UploadedFile::fake()->createWithContent('hidden.svg', '<?php echo 1;')] as $file) {
            $this->postJson(route('admin.media.store'), ['images' => [UploadedFile::fake()->image('valid.png'), $file]])->assertUnprocessable()->assertJsonValidationErrors('images.1');
        }
        $this->assertDatabaseCount('media_assets', $this->seededMediaCount + 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_svg_scripts_event_handlers_html_and_external_references_are_rejected(): void
    {
        $this->actingAs($this->administrator());
        foreach (['<script>alert(1)</script>', '<rect onload="alert(1)"/>', '<foreignObject><div>HTML</div></foreignObject>', '<use href="https://example.com/image.svg#icon"/>', '<rect style="fill:url(https://example.com/image.svg)"/>', '<style>.mark{fill:url(https://example.com/image.svg)}</style>'] as $content) {
            $svg = '<svg xmlns="http://www.w3.org/2000/svg">'.$content.'</svg>';
            $this->postJson(route('admin.media.store'), ['images' => [UploadedFile::fake()->createWithContent('unsafe.svg', $svg)]])->assertUnprocessable()->assertJsonValidationErrors('images.0');
        }
        $this->assertDatabaseCount('media_assets', $this->seededMediaCount + 0);
    }

    public function test_media_library_has_search_and_pagination_without_exposing_storage_paths(): void
    {
        $this->actingAs($this->administrator());
        for ($index = 1; $index <= 25; $index++) {
            $this->asset(['original_name' => 'photo-'.$index.'.png']);
        }
        $matching = $this->asset(['title' => 'Distinctive brand mark']);
        $this->getJson(route('admin.media.library'))->assertOk()->assertJsonCount(24, 'data')->assertJsonPath('total', 26 + $this->seededMediaCount)->assertJsonPath('last_page', 2)->assertJsonMissingPath('data.0.path');
        $this->getJson(route('admin.media.library', ['page' => 2]))->assertOk()->assertJsonCount(2 + $this->seededMediaCount, 'data')->assertJsonPath('current_page', 2);
        $this->getJson(route('admin.media.library', ['search' => 'Distinctive']))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $matching->id);
        $this->getJson(route('admin.media.library', ['search' => 'no-match']))->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_bulk_delete_removes_unused_images_and_keeps_content_branding_and_business_images(): void
    {
        $this->actingAs($this->administrator());
        $free = $this->asset();
        $freeSecond = $this->asset();
        $pageImage = $this->asset();
        $brandingImage = $this->asset();
        $businessImage = $this->asset();
        DB::table('pages')->where('slug', 'about')->update(['featured_image_id' => $pageImage->id]);
        DB::table('site_settings')->insert(['key' => 'branding.test_image', 'media_asset_id' => $brandingImage->id]);
        DB::table('office_records')->insert(['module' => 'clients', 'name' => 'Client', 'data' => json_encode(['featured_image_id' => $businessImage->id])]);
        $this->delete(route('admin.media.bulk-destroy'), ['ids' => [$free->id, $freeSecond->id, $pageImage->id, $brandingImage->id, $businessImage->id]])->assertRedirect()->assertSessionHas('success', '2 image(s) deleted. 3 image(s) kept because they are currently in use.');
        foreach ([$free, $freeSecond] as $asset) {
            $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);
            Storage::disk('public')->assertMissing($asset->path);
        }
        foreach ([$pageImage, $brandingImage, $businessImage] as $asset) {
            $this->assertDatabaseHas('media_assets', ['id' => $asset->id]);
            Storage::disk('public')->assertExists($asset->path);
        }
        $this->assertCount(3, Storage::disk('public')->allFiles());
    }

    public function test_nested_section_images_are_protected_from_single_and_bulk_deletion(): void
    {
        $this->actingAs($this->administrator());
        $asset = $this->asset();
        DB::table('website_sections')->insert(['key' => 'test.nested', 'scope' => 'home', 'data' => json_encode(['items' => [['image_id' => $asset->id]]])]);
        $this->delete(route('admin.media.destroy', $asset->id))->assertSessionHasErrors('media');
        $this->delete(route('admin.media.bulk-destroy'), ['ids' => [$asset->id]])->assertSessionHas('success', '0 image(s) deleted. 1 image(s) kept because they are currently in use.');
        Storage::disk('public')->assertExists($asset->path);
    }

    public function test_bulk_delete_validates_all_ids_before_removing_anything(): void
    {
        $this->actingAs($this->administrator());
        $asset = $this->asset();
        foreach ([[], [$asset->id, 999999], [$asset->id, $asset->id]] as $ids) {
            $this->deleteJson(route('admin.media.bulk-destroy'), ['ids' => $ids])->assertUnprocessable();
        }
        $this->assertDatabaseHas('media_assets', ['id' => $asset->id]);
        Storage::disk('public')->assertExists($asset->path);
    }

    public function test_failed_bulk_storage_operation_restores_previously_staged_files_and_rows(): void
    {
        $this->actingAs($this->administrator());
        $first = $this->asset();
        $second = $this->asset();
        $disk = Storage::disk('public');
        $mock = \Mockery::mock($disk);
        $calls = 0;
        $mock->shouldReceive('move')->andReturnUsing(function (string $from, string $to) use ($disk, &$calls): bool {
            $calls++;

            return $calls === 2 ? false : $disk->move($from, $to);
        });
        Storage::set('public', $mock);
        $this->delete(route('admin.media.bulk-destroy'), ['ids' => [$first->id, $second->id]])->assertStatus(500);
        $this->assertDatabaseCount('media_assets', $this->seededMediaCount + 2);
        $disk->assertExists($first->path);
        $disk->assertExists($second->path);
        $this->assertCount(2, $disk->allFiles());
    }

    public function test_all_editor_image_fields_render_the_shared_tabbed_picker(): void
    {
        $this->actingAs($this->administrator());
        $asset = $this->asset();
        $page = DB::table('pages')->where('slug', 'about')->first();
        DB::table('pages')->where('id', $page->id)->update(['featured_image_id' => $asset->id]);
        foreach ([route('admin.content.edit', ['pages', $page->id]), route('admin.settings'), route('admin.office.create', 'clients')] as $url) {
            $response = $this->get($url)->assertOk()->assertSee('data-media-open', false)->assertSee('id="admin-media-dialog"', false)->assertSee('Media library')->assertSee('Upload image')->assertSee('accept="image/*"', false);
            $response->assertDontSee('<select name="featured_image_id"', false)->assertDontSee('<select name="logo_light"', false);
        }
        $this->get(route('admin.media.index'))->assertOk()->assertSee('data-media-select-all', false)->assertSee('form="media-bulk-delete"', false)->assertSee('Maximum 8 MB per image');
    }

    public function test_guests_and_staff_cannot_browse_upload_or_bulk_delete_media(): void
    {
        $asset = $this->asset();
        $this->getJson(route('admin.media.library'))->assertUnauthorized();
        $this->deleteJson(route('admin.media.bulk-destroy'), ['ids' => [$asset->id]])->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $this->getJson(route('admin.media.library'))->assertForbidden();
        $this->postJson(route('admin.media.store'), ['images' => [UploadedFile::fake()->image('denied.png')]])->assertForbidden();
        $this->deleteJson(route('admin.media.bulk-destroy'), ['ids' => [$asset->id]])->assertForbidden();
        $this->assertDatabaseCount('media_assets', $this->seededMediaCount + 1);
    }
}

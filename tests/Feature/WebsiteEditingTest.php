<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\FrontendContentSeeder;
use Database\Seeders\WebsiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebsiteEditingTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, int> */
    private array $seededMediaIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(WebsiteSeeder::class);
        $this->seededMediaIds = DB::table('media_assets')->pluck('id')->all();
        config(['services.recaptcha.enabled' => false]);
        Storage::fake('public');
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        $user->role = 'administrator';
        $user->save();

        return $user;
    }

    private function upload(): int
    {
        $this->post('/admin/media', ['images' => [UploadedFile::fake()->image('workspace.png', 100, 60)], 'alt_text' => 'A connected workspace'])->assertRedirect()->assertSessionHasNoErrors();

        return DB::table('media_assets')->latest('id')->value('id');
    }

    /** @return array{data: array<string, mixed>, is_visible: int, sort_order: int} */
    private function sectionData(string $key, array $overrides = []): array
    {
        return ['data' => array_replace(json_decode(DB::table('website_sections')->where('key', $key)->value('data'), true), $overrides), 'is_visible' => 1, 'sort_order' => 10];
    }

    private function sectionId(string $key): int
    {
        return DB::table('website_sections')->where('key', $key)->value('id');
    }

    public function test_admin_can_upload_serve_describe_and_delete_an_unused_image(): void
    {
        $this->actingAs($this->administrator());
        $id = $this->upload();
        $asset = DB::table('media_assets')->find($id);
        Storage::disk('public')->assertExists($asset->path);
        $this->get('/media/'.$id)->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->patch('/admin/media/'.$id, ['alt_text' => 'Updated description'])->assertRedirect();
        $this->assertDatabaseHas('media_assets', ['id' => $id, 'alt_text' => 'Updated description', 'width' => 100, 'height' => 60]);
        $this->delete('/admin/media/'.$id)->assertRedirect();
        Storage::disk('public')->assertMissing($asset->path);
        $this->get('/media/'.$id)->assertNotFound();
    }

    public function test_upload_rejects_nonimages_unsafe_svg_and_oversized_files(): void
    {
        $this->actingAs($this->administrator());
        foreach ([UploadedFile::fake()->createWithContent('bad.php', '<?php echo 1;'), UploadedFile::fake()->createWithContent('bad.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'), UploadedFile::fake()->image('big.png')->size(8193)] as $file) {
            $this->post('/admin/media', ['images' => [$file], 'alt_text' => 'Invalid upload'])->assertSessionHasErrors('images.0');
        }
        $this->assertDatabaseCount('media_assets', count($this->seededMediaIds));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_featured_images_can_be_selected_removed_and_cannot_be_deleted_while_in_use(): void
    {
        $this->actingAs($this->administrator());
        $id = $this->upload();
        $page = DB::table('pages')->where('slug', 'about')->first();
        $data = ['title' => $page->title, 'description' => $page->description, 'content' => '', 'status' => 'published', 'sort_order' => 0, 'featured_image_id' => $id];
        $this->put('/admin/content/pages/'.$page->id, $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->get('/about')->assertSee('src="http://localhost/media/'.$id.'"', false)->assertSee('A connected workspace');
        $this->delete('/admin/media/'.$id)->assertSessionHasErrors('media');
        $this->assertDatabaseHas('media_assets', ['id' => $id]);
        $this->put('/admin/content/pages/'.$page->id, array_replace($data, ['featured_image_id' => null]))->assertRedirect();
        $this->get('/about')->assertDontSee('src="http://localhost/media/'.$id.'"', false);
        $this->delete('/admin/media/'.$id)->assertRedirect()->assertSessionHasNoErrors();
    }

    public function test_all_content_types_accept_featured_images_and_reject_unknown_image_ids(): void
    {
        $this->actingAs($this->administrator());
        $id = $this->upload();
        foreach (['services' => [], 'portfolio' => ['is_demonstration' => 1, 'category' => 'Software'], 'testimonials' => ['name' => 'Avery', 'company' => 'Example', 'consent_confirmed' => 1]] as $section => $extra) {
            $data = ['title' => 'Photo '.$section, 'slug' => 'photo-'.$section, 'description' => 'Content with an image.', 'status' => 'published', 'sort_order' => 0, 'featured_image_id' => $id] + $extra;
            $this->post('/admin/content/'.$section, $data)->assertRedirect()->assertSessionHasNoErrors();
            $this->get('/'.$section)->assertOk()->assertSee('src="http://localhost/media/'.$id.'"', false);
        }
        $this->post('/admin/content/services', ['title' => 'Invalid image', 'slug' => 'invalid-image', 'description' => 'Unknown image.', 'status' => 'draft', 'sort_order' => 0, 'featured_image_id' => 9999])->assertSessionHasErrors('featured_image_id');
    }

    public function test_sections_can_be_edited_hidden_reordered_and_have_items_changed(): void
    {
        $this->actingAs($this->administrator());
        $id = $this->sectionId('home.process');
        $data = $this->sectionData('home.process', ['title' => 'Our revised process', 'items' => [['title' => 'Explore together', 'text' => 'A new first step.', 'url' => '/contact']]]);
        $this->put('/admin/sections/'.$id, $data)->assertRedirect('/admin/sections')->assertSessionHasNoErrors();
        $this->get('/')->assertSee('Our revised process')->assertSee('Explore together')->assertDontSee('Feedback guides what comes next.');
        $this->put('/admin/sections/'.$id, array_replace($data, ['sort_order' => 0]))->assertRedirect();
        $this->get('/')->assertSeeInOrder(['Our revised process', 'How we can help']);
        $this->put('/admin/sections/'.$id, array_replace($data, ['is_visible' => 0]))->assertRedirect();
        $this->get('/')->assertDontSee('Our revised process');
    }

    public function test_section_images_are_publicly_displayed_and_protected_from_deletion(): void
    {
        $this->actingAs($this->administrator());
        $imageId = $this->upload();
        $id = $this->sectionId('about.values');
        $this->put('/admin/sections/'.$id, $this->sectionData('about.values', ['image_id' => $imageId]))->assertRedirect();
        $this->get('/about')->assertSee('src="http://localhost/media/'.$imageId.'"', false);
        $this->delete('/admin/media/'.$imageId)->assertSessionHasErrors('media');
    }

    public function test_navigation_branding_and_logos_are_editable_with_safe_links(): void
    {
        $this->actingAs($this->administrator());
        $id = $this->sectionId('shared.header_nav');
        $this->put('/admin/sections/'.$id, $this->sectionData('shared.header_nav', ['items' => [['title' => 'Our capabilities', 'url' => '/services']]]))->assertRedirect();
        $this->get('/')->assertSee('Our capabilities');
        $this->put('/admin/sections/'.$id, $this->sectionData('shared.header_nav', ['items' => [['title' => 'Unsafe', 'url' => 'javascript:alert(1)']]]))->assertSessionHasErrors('data.items.0.url');
        $imageId = $this->upload();
        $settings = config('website.branding');
        $settings['footer_explore_heading'] = 'Discover our company';
        $settings['logo_light'] = $imageId;
        $this->put('/admin/appearance', $settings)->assertRedirect()->assertSessionHasNoErrors();
        $this->get('/')->assertSee('Discover our company')->assertSee('src="http://localhost/media/'.$imageId.'"', false);
        $this->delete('/admin/media/'.$imageId)->assertSessionHasErrors('media');
    }

    public function test_dynamic_content_is_escaped_and_nonexistent_sections_are_rejected(): void
    {
        $this->actingAs($this->administrator());
        $id = $this->sectionId('about.values');
        $this->put('/admin/sections/'.$id, $this->sectionData('about.values', ['title' => '<script>alert(1)</script>']))->assertRedirect();
        $this->get('/about')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->put('/admin/sections/99999', $this->sectionData('about.values'))->assertNotFound();
        $this->put('/admin/sections/'.$id, $this->sectionData('about.values', ['image_id' => 99999]))->assertSessionHasErrors('data.image_id');
    }

    public function test_guests_and_nonadministrators_cannot_manage_media_or_sections(): void
    {
        foreach ([null, User::factory()->create()] as $user) {
            if ($user) {
                $this->actingAs($user);
            }
            $expected = $user ? 403 : 302;
            foreach (['/admin/media', '/admin/sections', '/admin/settings?tab=branding'] as $url) {
                $this->get($url)->assertStatus($expected);
            }
            $this->post('/admin/media', ['images' => [UploadedFile::fake()->image('denied.png')], 'alt_text' => 'Denied'])->assertStatus($expected);
            $this->put('/admin/sections/'.$this->sectionId('home.process'), $this->sectionData('home.process'))->assertStatus($expected);
            $this->put('/admin/appearance', config('website.branding'))->assertStatus($expected);
        }
        $this->assertDatabaseCount('media_assets', count($this->seededMediaIds));
    }

    public function test_every_editor_renders_and_frontend_login_links_are_removed(): void
    {
        $this->actingAs($this->administrator());
        foreach (['/admin/media', '/admin/sections', '/admin/settings?tab=branding'] as $url) {
            $this->get($url)->assertOk();
        }
        foreach (DB::table('website_sections')->pluck('id') as $id) {
            $this->get('/admin/sections/'.$id.'/edit')->assertOk();
        }
        foreach (['/', '/about', '/services', '/portfolio', '/testimonials', '/contact', '/engagement', '/privacy', '/terms'] as $url) {
            $this->get($url)->assertOk()->assertDontSee('href="http://localhost/login"', false);
        }
        DB::table('site_settings')->where('key', 'maintenance_enabled')->update(['value' => '1']);
        $this->post('/logout');
        $this->get('/')->assertStatus(503)->assertDontSee('href="http://localhost/login"', false);
        $this->get('/login')->assertOk();
    }

    public function test_existing_edited_sections_and_branding_survive_reseeding(): void
    {
        DB::table('website_sections')->where('key', 'about.values')->update(['data' => json_encode(['title' => 'Edited by our team', 'items' => []])]);
        DB::table('site_settings')->where('key', 'branding.tagline')->update(['value' => 'Our edited tagline']);
        $this->seed(FrontendContentSeeder::class);
        $this->get('/about')->assertSee('Edited by our team')->assertSee('Our edited tagline');
        $this->assertDatabaseCount('website_sections', count(config('website.sections')));
    }

    public function test_multiple_images_can_be_uploaded_without_title_or_alt_text(): void
    {
        $this->actingAs($this->administrator())->post('/admin/media', ['images' => [UploadedFile::fake()->image('first.png'), UploadedFile::fake()->image('second.jpg')]])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('media_assets', count($this->seededMediaIds) + 2);
        foreach (DB::table('media_assets')->whereNotIn('id', $this->seededMediaIds)->get() as $asset) {
            $this->assertNull($asset->title);
            $this->assertNull($asset->alt_text);
            Storage::disk('public')->assertExists($asset->path);
        }
        $this->get('/admin/media')->assertOk()->assertSee('name="images[]" multiple', false)->assertSee('first.png')->assertSee('second.jpg');
    }

    public function test_batch_metadata_is_optional_and_can_be_edited_or_cleared_per_image(): void
    {
        $this->actingAs($this->administrator())->post('/admin/media', ['images' => [UploadedFile::fake()->image('first.png'), UploadedFile::fake()->image('second.png')], 'title' => 'Our office', 'alt_text' => 'A shared workspace'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(2, DB::table('media_assets')->where('title', 'Our office')->where('alt_text', 'A shared workspace')->count());
        $id = DB::table('media_assets')->min('id');
        $this->patch('/admin/media/'.$id, ['title' => 'A new title', 'alt_text' => ''])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('media_assets', ['id' => $id, 'title' => 'A new title', 'alt_text' => null]);
        $this->patch('/admin/media/'.$id, ['title' => '', 'alt_text' => ''])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('media_assets', ['id' => $id, 'title' => null, 'alt_text' => null]);
    }

    public function test_invalid_batch_and_metadata_are_rejected_before_any_image_is_stored(): void
    {
        $this->actingAs($this->administrator())->post('/admin/media', ['images' => [UploadedFile::fake()->image('first.png'), UploadedFile::fake()->createWithContent('bad.php', '<?php echo 1;')]])->assertSessionHasErrors('images.1');
        $this->post('/admin/media', ['images' => [UploadedFile::fake()->image('valid.png')], 'title' => str_repeat('a', 256)])->assertSessionHasErrors('title');
        $this->post('/admin/media', ['images' => []])->assertSessionHasErrors('images');
        $this->post('/admin/media', ['images' => array_map(fn (int $index): UploadedFile => UploadedFile::fake()->image('image-'.$index.'.png'), range(1, 21))])->assertSessionHasErrors('images');
        $this->assertDatabaseCount('media_assets', count($this->seededMediaIds));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_batch_storage_failure_removes_previously_uploaded_files_and_rows(): void
    {
        $disk = Storage::disk('public');
        $mock = \Mockery::mock($disk);
        $calls = 0;
        $mock->shouldReceive('putFileAs')->twice()->andReturnUsing(function (mixed ...$arguments) use ($disk, &$calls): string|false {
            $calls++;

            return $calls === 2 ? false : $disk->putFileAs(...$arguments);
        });
        Storage::set('public', $mock);
        $this->actingAs($this->administrator())->post('/admin/media', ['images' => [UploadedFile::fake()->image('first.png'), UploadedFile::fake()->image('second.png')]])->assertStatus(500);
        $this->assertDatabaseCount('media_assets', count($this->seededMediaIds));
        $this->assertSame([], $disk->allFiles());
    }

    public function test_section_and_page_number_prefixes_are_removed_without_changing_edited_copy(): void
    {
        $portfolio = $this->sectionData('home.portfolio', ['eyebrow' => '03 / POSSIBILITIES IN PRACTICE', 'title' => 'Keep our custom portfolio title'])['data'];
        DB::table('website_sections')->where('key', 'home.portfolio')->update(['data' => json_encode($portfolio)]);
        $process = $this->sectionData('home.process', ['eyebrow' => '04 / HOW WE WORK'])['data'];
        DB::table('website_sections')->where('key', 'home.process')->update(['data' => json_encode($process)]);
        DB::table('pages')->where('slug', 'about')->update(['eyebrow' => '01 / OUR COMPANY']);
        $migration = require database_path('migrations/2026_11_17_062745_remove_number_prefixes_from_website_labels.php');
        $migration->up();
        $portfolio['eyebrow'] = 'POSSIBILITIES IN PRACTICE';
        $this->assertSame($portfolio, json_decode(DB::table('website_sections')->where('key', 'home.portfolio')->value('data'), true));
        $this->assertDatabaseHas('pages', ['slug' => 'about', 'eyebrow' => 'OUR COMPANY']);
        $this->get('/')->assertOk()->assertSee('POSSIBILITIES IN PRACTICE')->assertSee('HOW WE WORK')->assertSee('Keep our custom portfolio title')->assertDontSee('03 / POSSIBILITIES IN PRACTICE')->assertDontSee('04 / HOW WE WORK');
        $this->get('/about')->assertOk()->assertSee('OUR COMPANY')->assertDontSee('01 / OUR COMPANY');
        foreach (config('website.page_labels') as $label) {
            $this->assertDoesNotMatchRegularExpression('/^\d{1,2}\s*\//', $label);
        }
        foreach (config('website.sections') as $definition) {
            $label = $definition['fields']['eyebrow']['default'] ?? '';
            $this->assertDoesNotMatchRegularExpression('/^\d{1,2}\s*\//', $label);
        }
    }
}

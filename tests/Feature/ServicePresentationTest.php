<?php

namespace Tests\Feature;

use App\Models\Enquiry;
use App\Models\User;
use Database\Seeders\ServiceContentSeeder;
use Database\Seeders\WebsiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServicePresentationTest extends TestCase
{
    use RefreshDatabase;

    private int $seededMediaCount = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');
        $this->seed(WebsiteSeeder::class);
        $this->seededMediaCount = DB::table('media_assets')->count();
    }

    public function test_each_service_has_its_own_cover_and_two_service_specific_paragraphs(): void
    {
        $this->seed(ServiceContentSeeder::class);
        $this->assertDatabaseCount('media_assets', $this->seededMediaCount + count(config('service-content')));
        foreach (config('service-content') as $slug => $definition) {
            $service = DB::table('services')->where('slug', $slug)->first();
            $image = DB::table('media_assets')->find($service->featured_image_id);
            $this->assertSame('image/webp', $image->mime_type);
            $this->assertSame($definition['alt'], $image->alt_text);
            Storage::disk('public')->assertExists($image->path);
            $response = $this->get(route('services.show', $slug))->assertOk();
            $response->assertSee('src="'.route('media.show', $image->id).'"', false);
            foreach ($definition['paragraphs'] as $paragraph) {
                $response->assertSee($paragraph);
            }
            $this->assertSame(2, substr_count($response->getContent(), 'data-service-paragraph'));
            $this->get(route('media.show', $image->id))->assertOk()->assertHeader('Content-Type', 'image/webp');
        }
        $covers = DB::table('services')->whereIn('slug', array_keys(config('service-content')))->pluck('featured_image_id');
        $this->assertCount(count(config('service-content')), $covers->unique());
        $this->get('/services')->assertOk()->assertDontSee('service-number', false)->assertDontSee('02 / SERVICES')->assertSee('service-cover', false);
        $this->get('/')->assertOk()->assertDontSee('service-number', false);
    }

    public function test_reseeding_preserves_admin_copy_and_cover_changes_without_creating_duplicate_images(): void
    {
        $this->seed(ServiceContentSeeder::class);
        $chosenImage = DB::table('services')->where('slug', 'mobile-apps')->value('featured_image_id');
        DB::table('services')->where('slug', 'custom-software')->update(['content' => "A custom introduction.\n\nOur updated service offering.", 'featured_image_id' => $chosenImage]);
        $this->seed(ServiceContentSeeder::class);
        $this->assertDatabaseCount('media_assets', $this->seededMediaCount + count(config('service-content')));
        $this->assertDatabaseHas('services', ['slug' => 'custom-software', 'featured_image_id' => $chosenImage, 'content' => "A custom introduction.\n\nOur updated service offering."]);
        $this->get('/services/custom-software')->assertOk()->assertSee('A custom introduction.')->assertSee('Our updated service offering.');
    }

    public function test_legacy_drafts_and_archived_services_are_not_changed(): void
    {
        $id = DB::table('services')->insertGetId(['slug' => 'legacy-service', 'title' => 'Legacy service', 'description' => 'Original summary.', 'content' => 'Original legacy text.', 'status' => 'draft']);
        DB::table('services')->where('slug', 'software-testing')->update(['deleted_at' => now()]);
        $this->seed(ServiceContentSeeder::class);
        $this->assertDatabaseHas('services', ['id' => $id, 'content' => 'Original legacy text.', 'featured_image_id' => null, 'status' => 'draft']);
        $this->assertDatabaseHas('services', ['slug' => 'software-testing', 'featured_image_id' => null]);
        $this->assertDatabaseCount('media_assets', $this->seededMediaCount + count(config('service-content')) - 1);
    }

    public function test_service_paragraphs_keep_plain_text_escaping_and_support_windows_line_breaks(): void
    {
        DB::table('services')->where('slug', 'custom-software')->update(['content' => "First paragraph.\r\n\r\n<script>alert(1)</script>"]);
        $response = $this->get('/services/custom-software')->assertOk()->assertSee('First paragraph.')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->assertSame(2, substr_count($response->getContent(), 'data-service-paragraph'));
    }

    public function test_retired_service_is_removed_from_backend_frontend_contact_and_seeded_data(): void
    {
        $path = 'images/services/digital-media.webp';
        Storage::disk('public')->put($path, 'retired cover');
        $image = DB::table('media_assets')->insertGetId(['disk' => 'public', 'path' => $path, 'original_name' => 'digital-media.webp', 'mime_type' => 'image/webp', 'size_bytes' => 13]);
        $service = DB::table('services')->insertGetId(['slug' => 'digital-media', 'title' => 'Digital Media & Publishing', 'description' => 'Retired service.', 'content' => 'Retired copy.', 'featured_image_id' => $image, 'status' => 'published', 'published_at' => now()]);
        $enquiry = Enquiry::factory()->create(['project_type' => 'digital-media']);
        DB::table('pages')->where('slug', 'services')->update(['description' => 'Ten service lines. One practical approach. From the first sketch to ongoing support, we build technology around your business.']);
        $migration = require database_path('migrations/2026_11_17_060427_remove_digital_media_service.php');
        $migration->up();
        $this->seed(WebsiteSeeder::class);
        $this->seed(ServiceContentSeeder::class);
        $this->assertDatabaseMissing('services', ['slug' => 'digital-media']);
        $this->assertDatabaseMissing('media_assets', ['id' => $image]);
        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseHas('enquiries', ['id' => $enquiry->id, 'project_type' => 'digital-media']);
        $this->assertArrayNotHasKey('digital-media', config('howvanta.services'));
        $this->assertArrayNotHasKey('digital-media', config('service-content'));
        foreach (['/', '/services', '/contact', '/sitemap.xml'] as $url) {
            $this->get($url)->assertOk()->assertDontSee('Digital Media & Publishing')->assertDontSee('/services/digital-media');
        }
        $this->get('/services/digital-media')->assertNotFound();
        $this->post('/contact', ['project_type' => 'digital-media'])->assertSessionHasErrors('project_type');
        $admin = User::factory()->create();
        $admin->role = 'administrator';
        $admin->save();
        $this->actingAs($admin)->get(route('admin.content.index', 'services'))->assertOk()->assertDontSee('Digital Media & Publishing');
        $this->get(route('admin.content.edit', ['services', $service]))->assertNotFound();
        $this->assertStringNotContainsString('Ten service lines', DB::table('pages')->where('slug', 'services')->value('description'));
    }

    public function test_service_retirement_preserves_a_cover_still_used_by_other_content(): void
    {
        $path = 'images/services/digital-media.webp';
        Storage::disk('public')->put($path, 'shared cover');
        $image = DB::table('media_assets')->insertGetId(['disk' => 'public', 'path' => $path, 'original_name' => 'digital-media.webp', 'mime_type' => 'image/webp', 'size_bytes' => 12]);
        DB::table('services')->insert(['slug' => 'digital-media', 'title' => 'Digital Media & Publishing', 'description' => 'Retired service.', 'featured_image_id' => $image]);
        DB::table('pages')->where('slug', 'about')->update(['featured_image_id' => $image]);
        $migration = require database_path('migrations/2026_11_17_060427_remove_digital_media_service.php');
        $migration->up();
        $this->assertDatabaseMissing('services', ['slug' => 'digital-media']);
        $this->assertDatabaseHas('media_assets', ['id' => $image]);
        Storage::disk('public')->assertExists($path);
    }
}

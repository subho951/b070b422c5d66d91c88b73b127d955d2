<?php

namespace Tests\Feature;

use App\Models\Enquiry;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\WebsiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ContentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(WebsiteSeeder::class);
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        $user->role = 'administrator';
        $user->save();

        return $user;
    }

    /** @return array<string, mixed> */
    private function content(array $overrides = []): array
    {
        return array_merge(['title' => 'Inventory Software', 'slug' => 'inventory-software', 'description' => 'Track inventory across branches.', 'content' => 'Detailed software requirements.', 'status' => 'draft', 'sort_order' => 2], $overrides);
    }

    public function test_administrator_can_draft_publish_update_and_archive_a_service(): void
    {
        $this->actingAs($this->administrator())->post('/admin/content/services', $this->content())->assertRedirect('/admin/content/services');
        $id = DB::table('services')->where('slug', 'inventory-software')->value('id');
        $this->get('/services/inventory-software')->assertNotFound();
        $this->put('/admin/content/services/'.$id, $this->content(['status' => 'published', 'content' => 'Updated inventory details.']))->assertRedirect();
        $this->get('/services/inventory-software')->assertOk()->assertSee('Updated inventory details.');
        $this->get('/services')->assertSee('Inventory Software');
        $this->get('/sitemap.xml')->assertSee('/services/inventory-software');
        $this->delete('/admin/content/services/'.$id)->assertRedirect();
        $this->get('/services/inventory-software')->assertNotFound();
        $this->get('/services')->assertDontSee('Inventory Software');
        $this->get('/sitemap.xml')->assertDontSee('/services/inventory-software');
        $this->assertNotNull(DB::table('services')->where('id', $id)->value('deleted_at'));
    }

    public function test_portfolio_can_be_created_with_only_title_and_visibility(): void
    {
        $this->actingAs($this->administrator());
        for ($index = 0; $index < 2; $index++) {
            $this->post('/admin/content/portfolio', ['title' => 'Our Latest Project', 'status' => 'published'])->assertRedirect('/admin/content/portfolio')->assertSessionHasNoErrors();
        }
        foreach (['our-latest-project', 'our-latest-project-2'] as $slug) {
            $this->assertDatabaseHas('portfolio_projects', ['slug' => $slug, 'description' => '', 'sort_order' => 0, 'is_demonstration' => false]);
            $this->get('/portfolio/'.$slug)->assertOk();
        }
    }

    public function test_portfolio_optional_fields_can_be_cleared_without_changing_the_address(): void
    {
        $record = DB::table('portfolio_projects')->where('slug', 'alfa-network')->first();
        $this->actingAs($this->administrator())->put('/admin/content/portfolio/'.$record->id, [
            'title' => 'Updated title', 'status' => 'draft', 'slug' => '', 'description' => '', 'category' => '', 'content' => '',
            'challenge' => '', 'solution' => '', 'outcome' => '', 'sort_order' => '', 'is_demonstration' => '', 'featured_image_id' => '',
        ])->assertRedirect('/admin/content/portfolio')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('portfolio_projects', ['id' => $record->id, 'title' => 'Updated title', 'slug' => 'alfa-network', 'description' => '', 'category' => null, 'featured_image_id' => null, 'sort_order' => $record->sort_order]);
    }

    public function test_portfolio_still_requires_title_and_visibility_and_validates_optional_values(): void
    {
        $this->actingAs($this->administrator())->post('/admin/content/portfolio', [])->assertSessionHasErrors(['title', 'status']);
        $this->post('/admin/content/portfolio', ['title' => 'A project', 'status' => 'published', 'slug' => 'invalid slug', 'sort_order' => -1, 'featured_image_id' => 999999])->assertSessionHasErrors(['slug', 'sort_order', 'featured_image_id']);
    }

    public function test_admin_screens_render_and_unknown_sections_are_rejected(): void
    {
        $this->actingAs($this->administrator());
        foreach (['pages', 'services', 'portfolio', 'testimonials'] as $section) {
            $this->get('/admin/content/'.$section)->assertOk();
            if ($section !== 'pages') {
                $this->get('/admin/content/'.$section.'/create')->assertOk();
            }
        }
        $page = DB::table('pages')->first();
        $this->get('/admin/content/pages/'.$page->id.'/edit')->assertOk();
        $this->get('/admin/settings')->assertOk();
        $this->get('/admin/content/users')->assertNotFound();
        $this->get('/admin/content/services/99999/edit')->assertNotFound();
    }

    public function test_guests_and_staff_cannot_modify_content_settings_or_enquiries(): void
    {
        $enquiry = Enquiry::factory()->create();
        foreach ([null, User::factory()->create()] as $user) {
            if ($user) {
                $this->actingAs($user);
            }
            $expected = $user ? 403 : 302;
            $this->post('/admin/content/services', $this->content())->assertStatus($expected);
            $this->put('/admin/settings', [])->assertStatus($expected);
            $this->patch('/admin/enquiries/'.$enquiry->id, ['status' => 'closed'])->assertStatus($expected);
            $this->delete('/admin/content/services/1')->assertStatus($expected);
        }
        $this->assertDatabaseMissing('services', ['slug' => 'inventory-software']);
    }

    public function test_published_testimonials_require_consent_and_escape_feedback(): void
    {
        $this->actingAs($this->administrator());
        $feedback = ['title' => 'A clear process', 'name' => 'Avery Smith', 'company' => 'Example Ltd', 'description' => '<script>alert(1)</script>', 'status' => 'published', 'sort_order' => 0];
        $this->post('/admin/content/testimonials', $feedback)->assertSessionHasErrors('consent_confirmed');
        $this->assertDatabaseCount('testimonials', 0);
        $this->post('/admin/content/testimonials', $feedback + ['consent_confirmed' => 1])->assertRedirect();
        $this->get('/testimonials')->assertOk()->assertSee('Avery Smith')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        DB::table('testimonials')->update(['status' => 'draft']);
        $this->get('/testimonials')->assertDontSee('Avery Smith');
    }

    public function test_page_copy_changes_appear_on_the_website_and_page_addresses_are_fixed(): void
    {
        $this->actingAs($this->administrator());
        $id = DB::table('pages')->where('slug', 'about')->value('id');
        $data = ['title' => 'A thoughtful partnership', 'description' => 'A new introduction.', 'content' => 'A new body paragraph.', 'status' => 'published', 'sort_order' => 0];
        $this->put('/admin/content/pages/'.$id, $data)->assertRedirect();
        $this->get('/about')->assertSee('A thoughtful partnership')->assertSee('A new body paragraph.');
        $this->put('/admin/content/pages/'.$id, $data + ['slug' => 'renamed'])->assertSessionHasErrors('slug');
        $this->delete('/admin/content/pages/'.$id)->assertForbidden();
    }

    public function test_duplicate_and_invalid_slugs_and_untrusted_fields_are_rejected(): void
    {
        $this->actingAs($this->administrator());
        $this->post('/admin/content/services', $this->content(['slug' => 'custom-software']))->assertSessionHasErrors('slug');
        $this->post('/admin/content/services', $this->content(['slug' => '../invalid', 'status' => 'invalid']))->assertSessionHasErrors(['slug', 'status']);
        $this->post('/admin/content/services', $this->content(['created_by' => 999, 'deleted_at' => now()]))->assertRedirect();
        $this->assertDatabaseHas('services', ['slug' => 'inventory-software', 'created_by' => null, 'deleted_at' => null]);
    }

    public function test_maintenance_message_formats_markdown_without_allowing_unsafe_html_or_links(): void
    {
        DB::table('site_settings')->where('key', 'maintenance_enabled')->update(['value' => '1']);
        DB::table('site_settings')->where('key', 'maintenance_message')->update(['value' => "**Making things better.**\n\nPlease contact our team.\n\n<script>alert(1)</script>\n\n[Unsafe](javascript:alert(1))"]);
        $this->get('/')->assertStatus(503)
            ->assertSee('<strong>Making things better.</strong>', false)
            ->assertSee('<p>Please contact our team.</p>', false)
            ->assertSee('Still here to help.')
            ->assertSee('mailto:info@howvanta.com', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('href="javascript:', false);
    }

    public function test_maintenance_blocks_visitors_and_contact_posts_but_allows_admin_access(): void
    {
        DB::table('site_settings')->where('key', 'maintenance_enabled')->update(['value' => '1']);
        $this->get('/')->assertStatus(503)->assertHeader('Retry-After', '3600');
        $this->post('/contact', [])->assertStatus(503);
        $this->get('/login')->assertOk();
        $this->actingAs(User::factory()->create())->get('/')->assertStatus(503);
        $this->actingAs($this->administrator())->get('/')->assertStatus(503)->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/admin/settings')->assertOk();
        $this->put('/admin/settings', ['contact_email' => 'hello@example.com', 'contact_phone' => '+91 1234567890', 'contact_location' => 'Howrah, India', 'maintenance_enabled' => '0', 'maintenance_message' => 'We will return soon.'])->assertRedirect();
        $this->get('/contact')->assertSee('hello@example.com')->assertSee('+91 1234567890');
        $this->post('/logout');
        $this->get('/')->assertOk();
    }

    public function test_admin_can_update_enquiry_status_with_validation(): void
    {
        $enquiry = Enquiry::factory()->create();
        $this->actingAs($this->administrator())->patch('/admin/enquiries/'.$enquiry->id, ['status' => 'contacted'])->assertRedirect();
        $this->assertDatabaseHas('enquiries', ['id' => $enquiry->id, 'status' => 'contacted']);
        $this->patch('/admin/enquiries/'.$enquiry->id, ['status' => 'unknown'])->assertSessionHasErrors('status');
    }

    public function test_portfolio_content_can_be_published_and_updated(): void
    {
        $this->actingAs($this->administrator());
        $data = $this->content(['slug' => 'workflow-project', 'category' => 'Web application', 'is_demonstration' => 0, 'status' => 'published', 'challenge' => 'Disconnected processes.', 'solution' => 'One workspace.', 'outcome' => 'A unified interface.']);
        $this->post('/admin/content/portfolio', $data)->assertRedirect();
        $this->get('/portfolio/workflow-project')->assertOk()->assertSee('One workspace.')->assertDontSee('not a completed client engagement');
        $this->get('/portfolio')->assertSee('Inventory Software');
    }

    public function test_seeders_preserve_edited_content_and_existing_admin_password(): void
    {
        $this->seed(AdminSeeder::class);
        $this->post('/login', ['email' => 'admin@gmail.com', 'password' => '12345678'])->assertRedirect('/admin');
        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $admin->password = 'a-new-password';
        $admin->save();
        DB::table('pages')->where('slug', 'home')->update(['title' => 'Our updated headline']);
        $this->seed([WebsiteSeeder::class, AdminSeeder::class]);
        $this->assertDatabaseHas('pages', ['slug' => 'home', 'title' => 'Our updated headline']);
        $this->assertTrue(Hash::check('a-new-password', $admin->fresh()->password));
        $this->assertDatabaseCount('services', count(config('howvanta.services')));
    }
}

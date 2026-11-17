<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\WebsiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContentVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(WebsiteSeeder::class);
        config(['services.recaptcha.enabled' => false]);
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        $user->role = 'administrator';
        $user->save();

        return $user;
    }

    public function test_content_visibility_switches_publish_and_hide_without_changing_copy(): void
    {
        $this->actingAs($this->administrator());
        foreach (['pages' => ['pages', 'about', '/about'], 'services' => ['services', 'custom-software', '/services/custom-software'], 'portfolio' => ['portfolio_projects', 'alfa-network', '/portfolio/alfa-network']] as $section => [$table, $slug, $publicUrl]) {
            $record = DB::table($table)->where('slug', $slug)->first();
            $listUrl = '/admin/content/'.$section.'?page=2';
            $endpoint = '/admin/content/'.$section.'/'.$record->id.'/visibility';
            $this->get('/admin/content/'.$section)->assertOk()->assertSee('role="switch"', false)->assertSee($endpoint, false);
            $this->from($listUrl)->patch($endpoint, ['visible' => '0', 'title' => 'Ignore this title'])->assertRedirect($listUrl)->assertSessionHasNoErrors();
            $this->assertDatabaseHas($table, ['id' => $record->id, 'status' => 'draft', 'published_at' => null, 'title' => $record->title, 'description' => $record->description]);
            $this->get($publicUrl)->assertNotFound();
            $this->from($listUrl)->patch($endpoint, ['visible' => '1'])->assertRedirect($listUrl)->assertSessionHasNoErrors();
            $this->assertDatabaseHas($table, ['id' => $record->id, 'status' => 'published']);
            $this->assertNotNull(DB::table($table)->where('id', $record->id)->value('published_at'));
            $this->get($publicUrl)->assertOk();
        }
    }

    public function test_exclusive_switch_saves_from_the_listing_without_changing_publication_or_copy(): void
    {
        $record = DB::table('portfolio_projects')->where('slug', 'alfa-network')->first();
        $endpoint = '/admin/content/portfolio/'.$record->id.'/exclusive';
        $this->actingAs($this->administrator())->get('/admin/content/portfolio')->assertOk()->assertSee('<th>Exclusive</th>', false)->assertSee($endpoint, false)->assertSee('Exclusive for ALFA Network');
        $this->from('/admin/content/portfolio?page=2')->patch($endpoint, ['exclusive' => '0', 'status' => 'draft'])->assertRedirect('/admin/content/portfolio?page=2')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('portfolio_projects', ['id' => $record->id, 'is_exclusive' => false, 'status' => $record->status, 'published_at' => $record->published_at, 'title' => $record->title, 'description' => $record->description]);
        $this->get('/')->assertSee('href="http://localhost/portfolio/alfa-network"', false);
        $this->get('/portfolio')->assertSee('<h3>ALFA Network</h3>', false);
        $this->patch($endpoint, ['exclusive' => '1'])->assertSessionHasNoErrors();
        $this->get('/')->assertSee('href="http://localhost/portfolio/alfa-network"', false);
        $this->patch($endpoint, ['exclusive' => 'invalid'])->assertSessionHasErrors('exclusive');
        $this->patch($endpoint, [])->assertSessionHasErrors('exclusive');
        $this->patch('/admin/content/portfolio/999999/exclusive', ['exclusive' => 1])->assertNotFound();
        DB::table('portfolio_projects')->where('id', $record->id)->update(['deleted_at' => now()]);
        $this->patch($endpoint, ['exclusive' => 1])->assertNotFound();
    }

    public function test_homepage_listing_switch_changes_selection_without_changing_exclusivity_or_visibility(): void
    {
        $record = DB::table('portfolio_projects')->where('slug', 'alfa-network')->first();
        $endpoint = '/admin/content/portfolio/'.$record->id.'/homepage';
        $this->actingAs($this->administrator())->get('/admin/content/portfolio')->assertOk()->assertSee('<th>Homepage</th>', false)->assertSee($endpoint, false);
        $this->from('/admin/content/portfolio?page=2')->patch($endpoint, ['show_on_homepage' => '0', 'is_exclusive' => false])->assertRedirect('/admin/content/portfolio?page=2')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('portfolio_projects', ['id' => $record->id, 'show_on_homepage' => false, 'is_exclusive' => true, 'status' => $record->status]);
        $this->get('/')->assertDontSee('href="http://localhost/portfolio/alfa-network"', false)->assertSee('href="http://localhost/portfolio/e-school"', false);
        $this->patch($endpoint, ['show_on_homepage' => 1])->assertSessionHasNoErrors();
        $this->get('/')->assertSee('href="http://localhost/portfolio/alfa-network"', false);
        DB::table('portfolio_projects')->where('id', $record->id)->update(['status' => 'draft']);
        $this->get('/')->assertDontSee('href="http://localhost/portfolio/alfa-network"', false);
        $this->patch($endpoint, ['show_on_homepage' => 'invalid'])->assertSessionHasErrors('show_on_homepage');
        $this->patch($endpoint, [])->assertSessionHasErrors('show_on_homepage');
        $this->patch('/admin/content/portfolio/999999/homepage', ['show_on_homepage' => 1])->assertNotFound();
        DB::table('portfolio_projects')->where('id', $record->id)->update(['deleted_at' => now()]);
        $this->patch($endpoint, ['show_on_homepage' => 1])->assertNotFound();
    }

    public function test_testimonial_switch_requires_existing_consent_to_publish(): void
    {
        $this->actingAs($this->administrator())->post('/admin/content/testimonials', ['title' => 'Helpful team', 'name' => 'Avery', 'description' => 'They understood our requirements.', 'status' => 'draft', 'sort_order' => 0])->assertSessionHasNoErrors();
        $id = DB::table('testimonials')->value('id');
        $endpoint = '/admin/content/testimonials/'.$id.'/visibility';
        $this->patch($endpoint, ['visible' => 1])->assertSessionHasErrors('visibility');
        $this->assertDatabaseHas('testimonials', ['id' => $id, 'status' => 'draft', 'consent_confirmed' => false]);
        DB::table('testimonials')->where('id', $id)->update(['consent_confirmed' => true]);
        $this->patch($endpoint, ['visible' => 1])->assertSessionHasNoErrors();
        $this->get('/testimonials')->assertOk()->assertSee('Helpful team');
        $this->patch($endpoint, ['visible' => 0])->assertSessionHasNoErrors();
        $this->get('/testimonials')->assertDontSee('Helpful team');
    }

    public function test_section_visibility_switch_preserves_content_and_hides_the_section(): void
    {
        $record = DB::table('website_sections')->where('key', 'home.portfolio')->first();
        $endpoint = '/admin/sections/'.$record->id.'/visibility';
        $this->actingAs($this->administrator())->get('/admin/sections')->assertOk()->assertSee($endpoint, false);
        $this->from('/admin/sections')->patch($endpoint, ['visible' => 0])->assertRedirect('/admin/sections')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('website_sections', ['id' => $record->id, 'is_visible' => false, 'data' => $record->data, 'sort_order' => $record->sort_order]);
        $this->get('/')->assertDontSee('See what we have');
        $this->patch($endpoint, ['visible' => 1])->assertSessionHasNoErrors();
        $this->get('/')->assertSee('See what we have');
        $screenId = DB::table('website_sections')->where('key', 'maintenance.screen')->value('id');
        $this->patch('/admin/sections/'.$screenId.'/visibility', ['visible' => 0])->assertStatus(422);
    }

    public function test_switches_require_administrator_valid_values_and_existing_records(): void
    {
        $projectId = DB::table('portfolio_projects')->where('slug', 'alfa-network')->value('id');
        $sectionId = DB::table('website_sections')->value('id');
        $endpoints = ['/admin/content/portfolio/'.$projectId.'/visibility', '/admin/sections/'.$sectionId.'/visibility', '/admin/content/portfolio/'.$projectId.'/exclusive', '/admin/content/portfolio/'.$projectId.'/homepage'];
        foreach ($endpoints as $endpoint) {
            $this->patch($endpoint, ['visible' => 1])->assertRedirect('/login');
        }
        $this->actingAs(User::factory()->create());
        foreach ($endpoints as $endpoint) {
            $this->patch($endpoint, ['visible' => 1])->assertForbidden();
        }
        $this->actingAs($this->administrator());
        $this->patch($endpoints[0], ['visible' => 'invalid'])->assertSessionHasErrors('visible');
        $this->patch($endpoints[0], [])->assertSessionHasErrors('visible');
        $this->patch('/admin/content/unknown/1/visibility', ['visible' => 1])->assertNotFound();
        $this->patch('/admin/content/portfolio/999999/visibility', ['visible' => 1])->assertNotFound();
        $this->patch('/admin/sections/999999/visibility', ['visible' => 1])->assertNotFound();
        DB::table('portfolio_projects')->where('id', $projectId)->update(['deleted_at' => now()]);
        $this->patch($endpoints[0], ['visible' => 1])->assertNotFound();
    }
}

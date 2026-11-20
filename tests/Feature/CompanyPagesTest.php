<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ClientTestimonialsSeeder;
use Database\Seeders\CompanyPageContentSeeder;
use Database\Seeders\WebsiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CompanyPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(WebsiteSeeder::class);
        $this->seed(CompanyPageContentSeeder::class);
        Http::preventStrayRequests();
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        $user->role = 'administrator';
        $user->save();

        return $user;
    }

    public function test_about_privacy_and_terms_have_broad_editable_content_and_legal_navigation(): void
    {
        $this->get('/about')->assertOk()->assertSee('Technology shaped around real business needs')->assertSee('How we approach a project')->assertSee('A working relationship that fits your stage');
        foreach (['privacy', 'terms'] as $scope) {
            $this->assertGreaterThanOrEqual(12, count(config('company-content.sections')[$scope.'.body']['items']));
            $response = $this->get('/'.$scope)->assertOk()->assertSee('On this page')->assertSee('Last updated: 17 November 2026');
            foreach (config('company-content.sections')[$scope.'.body']['items'] as $item) {
                $response->assertSee($item['title']);
            }
        }
        $this->get('/privacy')->assertSee('id="privacy-body-information-you-choose-to-provide"', false)->assertSee('href="#privacy-body-information-you-choose-to-provide"', false)->assertSee('https://policies.google.com/privacy');
        $this->get('/terms')->assertSee('https://www.google.com/help/terms_maps/');
        $this->actingAs($this->administrator())->get(route('admin.sections.edit', DB::table('website_sections')->where('key', 'about.story')->value('id')))->assertOk()->assertSee('Technology shaped around real business needs');
    }

    public function test_content_seeder_does_not_overwrite_later_admin_changes(): void
    {
        DB::table('pages')->where('slug', 'about')->update(['title' => 'Our edited About page']);
        $record = DB::table('website_sections')->where('key', 'privacy.body')->first();
        $copy = json_decode($record->data, true);
        $copy['items'][0]['text'] = 'Our approved privacy wording.';
        DB::table('website_sections')->where('id', $record->id)->update(['data' => json_encode($copy), 'is_visible' => false]);
        $sectionCount = DB::table('website_sections')->count();
        $this->seed(CompanyPageContentSeeder::class);
        $this->seed(WebsiteSeeder::class);
        $this->assertDatabaseHas('pages', ['slug' => 'about', 'title' => 'Our edited About page']);
        $this->assertSame($copy, json_decode(DB::table('website_sections')->where('id', $record->id)->value('data'), true));
        $this->assertDatabaseHas('website_sections', ['id' => $record->id, 'is_visible' => false]);
        $this->assertDatabaseCount('website_sections', $sectionCount);
    }

    public function test_client_reviews_are_imported_once_and_only_approved_records_are_public(): void
    {
        $this->seed(ClientTestimonialsSeeder::class);
        $this->seed(ClientTestimonialsSeeder::class);
        $this->assertDatabaseCount('testimonials', 13);
        $this->assertSame(4, DB::table('testimonials')->where('status', 'published')->where('consent_confirmed', true)->count());
        $this->assertSame(9, DB::table('testimonials')->where('status', 'draft')->where('consent_confirmed', false)->whereNull('rating')->count());

        foreach (['/testimonials', '/'] as $url) {
            $this->get($url)->assertOk()->assertSee('Das Tours and Travels')->assertSee('Whiteboard Consultant')
                ->assertSee('Tilottoma Parlour')->assertSee('Hooghly TV')->assertSee('5 out of 5 stars')
                ->assertDontSee('Genius Medical Educare Foundation')->assertDontSee('Write a Google review');
        }
        $this->get('/privacy')->assertDontSee('automatic reviews');
        $this->get('/terms')->assertDontSee('Testimonials and Google reviews');
        Http::assertNothingSent();
    }

    public function test_administrator_can_edit_rating_and_choose_slider_visibility(): void
    {
        $this->seed(ClientTestimonialsSeeder::class);
        $record = DB::table('testimonials')->where('source_key', 'das-tours-travels-2026')->first();
        $url = route('admin.content.update', ['testimonials', $record->id]);
        $data = ['title' => $record->title, 'name' => $record->name, 'description' => $record->description,
            'consent_confirmed' => true, 'status' => 'published', 'sort_order' => 10, 'rating' => 5,
            'reviewed_on' => '2026-11-13', 'show_on_homepage' => false];
        $this->actingAs($this->administrator())->put($url, array_replace($data, ['rating' => 6]))
            ->assertSessionHasErrors('rating');
        $this->put($url, $data)->assertSessionHasNoErrors();
        $this->get('/testimonials')->assertSee('Das Tours and Travels');
        $this->get('/')->assertDontSee('Das Tours and Travels');
        $this->get('/admin/settings')->assertDontSee('Google reviews');
    }
}

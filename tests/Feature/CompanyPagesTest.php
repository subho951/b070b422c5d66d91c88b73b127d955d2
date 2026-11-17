<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CompanyPageContentSeeder;
use Database\Seeders\WebsiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
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
        config(['services.google_reviews.enabled' => false, 'services.google_reviews.api_key' => null, 'services.google_reviews.place_id' => null]);
        Http::preventStrayRequests();
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        $user->role = 'administrator';
        $user->save();

        return $user;
    }

    private function enableReviews(): void
    {
        config(['services.google_reviews.enabled' => true, 'services.google_reviews.api_key' => 'private-google-key', 'services.google_reviews.place_id' => 'ChIJHowvantaTestBusiness']);
    }

    /** @return array<string, mixed> */
    private function googleResponse(): array
    {
        return [
            'id' => 'ChIJHowvantaTestBusiness', 'rating' => 4.3, 'userRatingCount' => 12,
            'googleMapsLinks' => ['reviewsUri' => 'https://www.google.com/maps/reviews/business', 'writeAReviewUri' => 'https://www.google.com/maps/reviews/write'],
            'reviews' => [
                ['rating' => 4, 'text' => ['text' => 'Translated review'], 'originalText' => ['text' => 'Clear communication and a useful delivery process.'], 'authorAttribution' => ['displayName' => 'Example Reviewer', 'uri' => 'https://www.google.com/maps/contrib/123', 'photoUri' => 'https://lh3.googleusercontent.com/example-avatar'], 'googleMapsUri' => 'https://www.google.com/maps/reviews/original-123', 'relativePublishTimeDescription' => 'a month ago'],
                ['rating' => 2, 'text' => ['text' => '<script>alert(1)</script>'], 'authorAttribution' => ['displayName' => 'Another Reviewer', 'uri' => 'javascript:alert(1)', 'photoUri' => 'https://untrusted.example/avatar'], 'googleMapsUri' => 'https://www.google.com/maps/reviews/original-456'],
            ],
            'attributions' => [['provider' => 'Example attribution', 'providerUri' => 'https://example.com/attribution']],
        ];
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

    public function test_profile_links_work_without_credentials_and_no_rating_or_review_is_invented(): void
    {
        $this->get('/testimonials')->assertOk()->assertSee('Read our reviews on Google.')->assertSee(config('services.google_reviews.profile_url'))->assertSee('Write a Google review')->assertDontSee('class="google-rating"', false)->assertDontSee('google-review-card', false);
        Http::assertNothingSent();
        $this->assertDatabaseCount('testimonials', 0);
    }

    public function test_google_reviews_use_the_api_with_attributions_original_text_and_no_positive_only_filter(): void
    {
        $this->enableReviews();
        Http::fake(['places.googleapis.com/*' => Http::response($this->googleResponse())]);
        $this->get('/testimonials')->assertOk()->assertSee('4.3')->assertSee('12 Google reviews')->assertSee('Example Reviewer')->assertSee('Another Reviewer')->assertSee('Clear communication and a useful delivery process.')->assertDontSee('Translated review')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('javascript:alert(1)', false)->assertDontSee('https://untrusted.example/avatar')->assertSee('https://www.google.com/maps/reviews/original-123')->assertSee('Google Maps')->assertSee('ordered by relevance')->assertSee('Example attribution')->assertDontSee('private-google-key');
        Http::assertSent(fn ($request): bool => $request->url() === 'https://places.googleapis.com/v1/places/ChIJHowvantaTestBusiness?languageCode=en' && $request->hasHeader('X-Goog-Api-Key', 'private-google-key') && str_contains($request->header('X-Goog-FieldMask')[0], 'reviews'));
        $this->get('/testimonials')->assertOk();
        Http::assertSentCount(2);
        $this->assertDatabaseCount('testimonials', 0);
    }

    public function test_provider_errors_invalid_responses_and_timeouts_keep_the_profile_links_available(): void
    {
        $this->enableReviews();
        Http::fakeSequence('places.googleapis.com/*')->push(['error' => 'provider-secret-error'], 403)->pushFailedConnection()->push('invalid-json')->push(['id' => 'different-business', 'rating' => 5, 'userRatingCount' => 20]);
        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->get('/testimonials')->assertOk()->assertSee('Read reviews on Google')->assertDontSee('provider-secret-error')->assertDontSee('private-google-key')->assertDontSee('class="google-rating"', false);
        }
    }

    public function test_google_review_settings_encrypt_credentials_preserve_blank_keys_and_do_not_fetch_on_save(): void
    {
        $this->actingAs($this->administrator());
        $data = ['enabled' => 1, 'profile_url' => 'https://www.google.com/maps?cid=123', 'review_url' => 'https://g.page/r/business/review', 'place_id' => 'ChIJHowvantaTestBusiness', 'google_api_key' => 'encrypted-places-key'];
        $this->put(route('admin.settings.reviews'), $data)->assertRedirect(route('admin.settings', ['tab' => 'reviews']))->assertSessionHasNoErrors();
        $stored = DB::table('site_settings')->where('key', 'google_reviews.api_key')->first();
        $this->assertSame('encrypted', $stored->type);
        $this->assertSame('encrypted-places-key', Crypt::decryptString($stored->value));
        $this->get(route('admin.settings', ['tab' => 'reviews']))->assertOk()->assertSee('A key is configured.')->assertDontSee('encrypted-places-key');
        $this->put(route('admin.settings.reviews'), array_replace($data, ['google_api_key' => '']))->assertSessionHasNoErrors();
        $this->assertSame($stored->value, DB::table('site_settings')->where('key', 'google_reviews.api_key')->value('value'));
        Http::assertNothingSent();
    }

    public function test_review_settings_validate_credentials_and_google_links_without_flashing_the_secret(): void
    {
        $this->actingAs($this->administrator());
        $this->put(route('admin.settings.reviews'), ['enabled' => 1, 'profile_url' => 'https://www.google.com/maps?cid=123'])->assertSessionHasErrors(['place_id', 'google_api_key']);
        $this->put(route('admin.settings.reviews'), ['enabled' => 0, 'profile_url' => 'https://example.com/fake-google', 'google_api_key' => 'do-not-flash-this-key'])->assertSessionHasErrors('profile_url');
        $this->assertArrayNotHasKey('google_api_key', session()->getOldInput());
        $this->assertDatabaseMissing('site_settings', ['key' => 'google_reviews.api_key']);
    }

    public function test_guests_and_nonadministrators_cannot_change_the_google_review_connection(): void
    {
        $this->put(route('admin.settings.reviews'), [])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->put(route('admin.settings.reviews'), [])->assertForbidden();
        $this->assertDatabaseMissing('site_settings', ['key' => 'google_reviews.enabled']);
    }
}

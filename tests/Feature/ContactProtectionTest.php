<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\WebsiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContactProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(WebsiteSeeder::class);
        config(['services.recaptcha.enabled' => false]);
        Http::preventStrayRequests();
    }

    /** @return array<string, mixed> */
    private function enquiry(array $overrides = []): array
    {
        return array_merge(['name' => 'Avery Smith', 'email' => 'avery@example.com', 'company' => 'Example Ltd', 'country' => 'India', 'phone' => '+91 62893 39520', 'project_type' => 'custom-software', 'description' => 'We need to improve inventory management across our business.', 'privacy_consent' => 1, 'budget' => '5000_15000'], $overrides);
    }

    private function enableRecaptcha(): void
    {
        config(['services.recaptcha.enabled' => true, 'services.recaptcha.site_key' => 'test-site-key', 'services.recaptcha.secret_key' => 'test-secret', 'services.recaptcha.minimum_score' => 0.5, 'services.recaptcha.hostnames' => ['localhost']]);
    }

    /** @return array<string, mixed> */
    private function verdict(array $overrides = []): array
    {
        return array_merge(['success' => true, 'score' => 0.9, 'action' => 'contact', 'hostname' => 'localhost', 'challenge_ts' => now()->toIso8601String()], $overrides);
    }

    public function test_contact_form_has_country_options_required_phone_and_local_currency_ranges(): void
    {
        $this->get('/contact')->assertOk()->assertSee('Select your country')->assertSee('data-currency="INR"', false)->assertSee('data-currency="GBP"', false)->assertSee('name="phone"', false)->assertSee('data-budget-ranges', false);
        $this->from('/contact')->post('/contact', $this->enquiry(['phone' => '']))->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('enquiries', 0);
    }

    public function test_budget_currency_is_derived_from_country_and_not_client_input(): void
    {
        foreach (['India' => 'INR', 'United Kingdom' => 'GBP', 'United States' => 'USD', 'Germany' => 'EUR', 'Kuwait' => 'KWD'] as $country => $currency) {
            $this->post('/contact', $this->enquiry(['country' => $country, 'budget_currency' => 'XXX']))->assertRedirect('/contact');
            $this->assertDatabaseHas('enquiries', ['country' => $country, 'budget_currency' => $currency, 'budget' => $currency.' - 5,000 to 15,000']);
        }
    }

    public function test_invalid_countries_phones_and_ranges_are_rejected_and_array_country_can_be_redisplayed(): void
    {
        foreach ([['country' => 'Atlantis'], ['phone' => 'not a phone'], ['budget' => 'USD - Under 5,000'], ['country' => ['India']]] as $invalid) {
            $this->from('/contact')->post('/contact', $this->enquiry($invalid))->assertSessionHasErrors(array_key_first($invalid));
        }
        $this->get('/contact')->assertOk();
        $this->assertDatabaseCount('enquiries', 0);
    }

    public function test_budget_is_optional_and_currency_is_still_saved(): void
    {
        $this->post('/contact', $this->enquiry(['budget' => null]))->assertRedirect('/contact');
        $this->assertDatabaseHas('enquiries', ['budget' => null, 'budget_currency' => 'INR']);
    }

    public function test_recaptcha_rejects_missing_token_without_contacting_google(): void
    {
        $this->enableRecaptcha();
        $this->post('/contact', $this->enquiry())->assertSessionHasErrors('recaptcha');
        Http::assertNothingSent();
        $this->assertDatabaseCount('enquiries', 0);
    }

    public function test_valid_recaptcha_allows_enquiry_and_keeps_secret_and_token_out_of_storage(): void
    {
        $this->enableRecaptcha();
        Http::fake(['www.google.com/recaptcha/api/siteverify' => Http::response($this->verdict())]);
        $this->post('/contact', $this->enquiry(['g-recaptcha-response' => 'a-fresh-token']))->assertRedirect('/contact')->assertSessionHasNoErrors();
        Http::assertSent(fn ($request): bool => $request['secret'] === 'test-secret' && $request['response'] === 'a-fresh-token');
        $this->assertDatabaseCount('enquiries', 1);
    }

    public function test_recaptcha_rejects_low_scores_wrong_actions_hosts_expired_and_malformed_results(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->enableRecaptcha();
        $sequence = Http::fakeSequence('www.google.com/recaptcha/api/siteverify');
        foreach ([['score' => 0.1], ['action' => 'login'], ['hostname' => 'untrusted.example'], ['success' => false], ['score' => null], ['score' => 1.1], ['challenge_ts' => now()->subMinutes(3)->toIso8601String()], ['challenge_ts' => now()->addMinutes(3)->toIso8601String()], ['challenge_ts' => 'invalid'], ['challenge_ts' => '']] as $invalid) {
            $sequence->push($this->verdict($invalid));
            $response = $this->post('/contact', $this->enquiry(['g-recaptcha-response' => 'a-token']));
            $this->assertSame(302, $response->status(), json_encode($invalid));
            $response->assertSessionHasErrors('recaptcha');
        }
        $this->assertDatabaseCount('enquiries', 0);
    }

    public function test_recaptcha_fails_closed_on_google_errors_and_missing_configuration(): void
    {
        $this->enableRecaptcha();
        Http::fakeSequence('www.google.com/recaptcha/api/siteverify')->pushFailedConnection()->push('unavailable', 503);
        $this->post('/contact', $this->enquiry(['g-recaptcha-response' => 'a-token']))->assertSessionHasErrors('recaptcha');
        $this->post('/contact', $this->enquiry(['g-recaptcha-response' => 'another-token']))->assertSessionHasErrors('recaptcha');
        config(['services.recaptcha.secret_key' => null]);
        $this->post('/contact', $this->enquiry(['g-recaptcha-response' => 'third-token']))->assertSessionHasErrors('recaptcha');
        $this->assertDatabaseCount('enquiries', 0);
    }

    public function test_login_and_admin_mutations_are_protected_while_pages_still_load(): void
    {
        $this->enableRecaptcha();
        $this->get('/login')->assertOk()->assertSee('recaptcha-site-key')->assertDontSee('test-secret');
        $user = User::factory()->create(['password' => 'a-test-password']);
        $user->role = 'administrator';
        $user->save();
        $this->post('/login', ['email' => $user->email, 'password' => 'a-test-password'])->assertSessionHasErrors('recaptcha');
        $this->assertGuest();
        Http::fakeSequence('www.google.com/recaptcha/api/siteverify')->push($this->verdict(['action' => 'login']))->push($this->verdict(['action' => 'admin']));
        $this->post('/login', ['email' => $user->email, 'password' => 'a-test-password', 'g-recaptcha-response' => 'login-token'])->assertRedirect('/admin');
        $this->get('/admin/settings')->assertOk()->assertSee('recaptcha-site-key');
        $this->post('/admin/content/services', [])->assertSessionHasErrors('recaptcha');
        $this->post('/admin/content/services', ['title' => 'Protected service', 'slug' => 'protected-service', 'description' => 'Created through a verified administrator action.', 'status' => 'draft', 'sort_order' => 0, 'g-recaptcha-response' => 'admin-token'])->assertRedirect('/admin/content/services');
        $this->assertDatabaseHas('services', ['slug' => 'protected-service']);
        $this->post('/logout')->assertRedirect('/');
        Http::assertSentCount(2);
        $this->assertGuest();
    }

    public function test_authenticated_admin_can_view_contact_and_logout_when_recaptcha_is_unavailable(): void
    {
        $this->enableRecaptcha();
        config(['services.recaptcha.secret_key' => null]);
        $user = User::factory()->create();
        $user->role = 'administrator';
        $user->save();
        $this->actingAs($user)->get('/contact')->assertOk()->assertSee('Phone number');
        $this->get('/admin/settings')->assertOk()->assertSee('data-recaptcha-exempt', false);
        $this->post('/logout')->assertRedirect('/')->assertSessionHasNoErrors();
        $this->assertGuest();
        Http::assertNothingSent();
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('data-password-toggle', false)->assertSee('aria-label="Show password"', false);
    }

    public function test_contact_validation_errors_and_password_toggle_render_without_repopulating_password(): void
    {
        $this->from('/contact')->post('/contact', $this->enquiry(['phone' => '']))->assertSessionHasErrors('phone');
        $this->get('/contact')->assertOk();
        $this->withViewErrors(['phone' => 'A phone number is required.'])
            ->blade('<x-field name="phone" label="Phone number" type="tel" :required="true"/>')
            ->assertSee('phone-error', false)->assertSee('A phone number is required.');
        $this->withSession(['_old_input' => ['email' => 'avery@example.com', 'password' => 'never-redisplay-this']])
            ->get('/login')->assertOk()->assertSee('data-password-toggle', false)->assertDontSee('never-redisplay-this');
    }

    public function test_local_development_hosts_bypass_recaptcha_in_frontend_and_backend(): void
    {
        $this->enableRecaptcha();
        $this->app->instance('env', 'local');
        $this->withSession(['_token' => 'local-csrf-test']);

        foreach (['localhost', '127.0.0.1', '[::1]'] as $host) {
            $this->get('http://'.$host.'/contact')->assertOk()->assertDontSee('recaptcha-site-key')->assertDontSee('google.com/recaptcha/api.js');
            $this->post('http://'.$host.'/contact', $this->enquiry(['_token' => 'local-csrf-test']))->assertRedirect()->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('enquiries', 3);
        Http::assertNothingSent();
    }

    public function test_live_domain_requires_recaptcha_even_in_a_local_environment(): void
    {
        $this->enableRecaptcha();
        $this->app->instance('env', 'local');
        $this->withSession(['_token' => 'local-csrf-test']);
        config(['services.recaptcha.hostnames' => ['howvanta.com', 'www.howvanta.com']]);

        foreach (['howvanta.com', 'www.howvanta.com', 'localhost.example.com'] as $host) {
            $this->get('https://'.$host.'/contact')->assertOk()->assertSee('recaptcha-site-key');
            $this->post('https://'.$host.'/contact', $this->enquiry(['_token' => 'local-csrf-test']))->assertSessionHasErrors('recaptcha');
        }
        $this->assertDatabaseCount('enquiries', 0);
        Http::assertNothingSent();

        Http::fake(['www.google.com/recaptcha/api/siteverify' => Http::response($this->verdict(['hostname' => 'howvanta.com']))]);
        $this->post('https://howvanta.com/contact', $this->enquiry(['g-recaptcha-response' => 'live-domain-token', '_token' => 'local-csrf-test']))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('enquiries', 1);
        Http::assertSentCount(1);
    }

    public function test_production_environment_does_not_allow_localhost_bypass(): void
    {
        $this->enableRecaptcha();
        $this->app->instance('env', 'production');
        $this->withSession(['_token' => 'local-csrf-test']);
        $this->get('http://localhost/contact')->assertOk()->assertSee('recaptcha-site-key');
        $this->post('http://localhost/contact', $this->enquiry(['_token' => 'local-csrf-test']))->assertSessionHasErrors('recaptcha');
        $this->assertDatabaseCount('enquiries', 0);
        Http::assertNothingSent();
    }
}

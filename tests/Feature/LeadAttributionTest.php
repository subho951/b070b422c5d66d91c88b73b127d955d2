<?php

namespace Tests\Feature;

use App\Models\Enquiry;
use Database\Seeders\WebsiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class LeadAttributionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(WebsiteSeeder::class);
        config(['services.recaptcha.enabled' => false, 'services.ga4.measurement_id' => 'G-TEST123']);
    }

    public function test_consent_preserves_attribution_across_navigation_and_confirms_a_consultation_only_once(): void
    {
        $firstTouch = [
            'utm_source' => 'google', 'utm_medium' => 'organic', 'utm_campaign' => 'gbp',
            'utm_content' => 'listing', 'utm_term' => 'web development',
        ];
        $this->get('/?'.http_build_query($firstTouch))->assertOk()
            ->assertDontSee('googletagmanager.com')->assertSessionMissing('lead_attribution');
        $this->postJson('/analytics-consent', ['allowed' => true, 'campaign' => $firstTouch])
            ->assertOk()->assertJsonPath('lead_source', 'gbp');
        $this->get('/services')->assertOk();
        $this->get('/portfolio?utm_source=instagram&utm_medium=social')->assertOk();
        $this->get('/contact')->assertOk();

        foreach (['whatsapp', 'phone', 'email', 'booking', 'form_start'] as $action) {
            $this->postJson('/contact-click', ['action' => $action])->assertNoContent();
        }

        $this->from('/contact')->post('/contact', ['intent' => 'consultation'])->assertSessionHasErrors();
        $this->get('/contact/thank-you')->assertDontSee('data-analytics-conversion');
        $this->assertDatabaseCount('enquiries', 0);

        $this->post('/contact', $this->enquiry())->assertRedirect('/contact/thank-you');
        $enquiry = Enquiry::sole();
        $this->assertSame($firstTouch + ['source' => 'gbp'], $enquiry->first_touch_attribution);
        $this->assertSame(['utm_source' => 'instagram', 'utm_medium' => 'social', 'source' => 'instagram'], $enquiry->last_touch_attribution);
        $this->assertSame('instagram', $enquiry->lead_source);
        $this->get('/contact/thank-you')->assertSee('data-analytics-conversion="consultation"', false);
        $this->get('/contact/thank-you')->assertDontSee('data-analytics-conversion');
    }

    public function test_withdrawing_consent_clears_attribution_and_keeps_enquiries_working(): void
    {
        $this->postJson('/analytics-consent', ['allowed' => true, 'campaign' => ['utm_source' => 'google', 'utm_campaign' => 'gbp']])->assertOk();
        $this->postJson('/analytics-consent', ['allowed' => false])->assertOk()->assertSessionMissing('lead_attribution');
        $this->get('/contact?utm_source=google&utm_campaign=gbp')->assertOk()->assertSessionMissing('lead_attribution');
        $this->post('/contact', $this->enquiry())->assertRedirect('/contact/thank-you');
        $this->get('/contact/thank-you')->assertSee('Your enquiry has been received')->assertDontSee('data-analytics-conversion');
        $this->assertDatabaseHas('enquiries', ['lead_source' => null, 'first_touch_attribution' => null, 'last_touch_attribution' => null]);
    }

    #[TestWith(['https://www.google.co.in/', [], 'google_organic'])]
    #[TestWith(['', ['utm_source' => 'google', 'utm_medium' => 'cpc'], 'google_ads'])]
    #[TestWith(['', ['gclid' => 'test-click'], 'google_ads'])]
    #[TestWith(['https://l.facebook.com/', [], 'facebook'])]
    #[TestWith(['https://l.instagram.com/', [], 'instagram'])]
    #[TestWith(['https://example.com/', [], 'referral'])]
    #[TestWith(['', [], 'direct'])]
    public function test_consent_classifies_the_landing_source(string $referrer, array $campaign, string $source): void
    {
        $this->postJson('/analytics-consent', ['allowed' => true, 'campaign' => $campaign, 'referrer' => $referrer])
            ->assertOk()->assertJsonPath('lead_source', $source);
    }

    private function enquiry(): array
    {
        return [
            'name' => 'Avery Smith', 'email' => 'avery@example.com', 'intent' => 'consultation',
            'description' => 'Please help us plan an internal tool.', 'privacy_consent' => true,
        ];
    }
}

<?php

namespace Tests\Feature;

use App\Models\Enquiry;
use App\Models\User;
use App\Support\CommunicationSettings;
use Database\Seeders\WebsiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
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

    private function administrator(): User
    {
        $user = User::factory()->create(['password' => 'current-password']);
        $user->role = 'administrator';
        $user->save();

        return $user;
    }

    /** @return array<string, mixed> */
    private function emailData(): array
    {
        return ['enabled' => 1, 'host' => 'smtp.example.com', 'port' => 587, 'scheme' => 'smtp', 'username' => 'mailer@example.com', 'smtp_password' => 'mail-secret', 'from_address' => 'info@example.com', 'from_name' => 'Howvanta', 'notification_email' => 'admin@example.com'];
    }

    /** @return array<string, mixed> */
    private function smsData(): array
    {
        return ['sms_authentication_key' => 'sms-secret', 'sender_id' => 'HOWVNT', 'base_url' => 'https://sms.example.com/api'];
    }

    public function test_branding_is_part_of_general_settings_and_appearance_redirects_there(): void
    {
        $this->actingAs($this->administrator());
        foreach (['general', 'maintenance', 'password', 'email', 'sms'] as $tab) {
            $this->get('/admin/settings?tab='.$tab)->assertOk()->assertSee('aria-current="page"', false);
        }
        $this->get('/admin/appearance')->assertRedirect('/admin/settings?tab=general');
        $this->get('/admin/settings?tab=general')->assertSee('logo_light', false)->assertSee('site_name', false)->assertSee('contact_email', false)->assertDontSee('href="http://localhost/admin/settings?tab=branding"', false);
    }

    public function test_general_and_maintenance_tabs_save_independently(): void
    {
        $this->actingAs($this->administrator());
        $this->put('/admin/settings', ['_tab' => 'general', 'contact_email' => 'hello@example.com', 'contact_phone' => '+91 1234567890', 'contact_location' => 'Howrah'])->assertRedirect('/admin/settings?tab=general')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('site_settings', ['key' => 'contact_email', 'value' => 'hello@example.com']);
        $this->put('/admin/settings', ['_tab' => 'maintenance', 'maintenance_enabled' => 0, 'maintenance_message' => 'Making improvements.'])->assertRedirect('/admin/settings?tab=maintenance')->assertSessionHasNoErrors();
    }

    public function test_saving_maintenance_closes_public_pages_for_everyone_and_live_reopens_them(): void
    {
        $admin = $this->administrator();
        $this->actingAs($admin)->put('/admin/settings', ['_tab' => 'maintenance', 'maintenance_enabled' => '1', 'maintenance_message' => 'Scheduled website improvements.'])
            ->assertRedirect('/admin/settings?tab=maintenance')->assertSessionHasNoErrors();
        foreach (['/', '/about', '/contact', '/services/custom-software', '/portfolio/alfa-network', '/sitemap.xml'] as $url) {
            $this->get($url)->assertStatus(503)->assertSee('Scheduled website improvements.')->assertHeader('Retry-After', '3600');
        }
        $this->post('/contact', [])->assertStatus(503);
        $this->get('/admin/settings?tab=maintenance')->assertOk();
        $this->post('/logout')->assertRedirect();
        $this->get('/')->assertStatus(503);
        $this->get('/login')->assertOk();
        $this->actingAs($admin)->put('/admin/settings', ['_tab' => 'maintenance', 'maintenance_enabled' => '0', 'maintenance_message' => 'Scheduled website improvements.'])
            ->assertRedirect('/admin/settings?tab=maintenance')->assertSessionHasNoErrors();
        $this->get('/')->assertOk();
        $this->post('/logout');
        $this->get('/contact')->assertOk();
    }

    public function test_password_change_requires_current_password_and_confirmation(): void
    {
        $admin = $this->administrator();
        $this->actingAs($admin)->from('/admin/settings?tab=password')->put('/admin/settings/password', ['current_password' => 'wrong-password', 'password' => 'new-password-123', 'password_confirmation' => 'mismatch'])->assertSessionHasErrors(['current_password', 'password']);
        $this->assertTrue(Hash::check('current-password', $admin->fresh()->password));
        $this->assertArrayNotHasKey('current_password', session()->getOldInput());
        $this->assertArrayNotHasKey('password', session()->getOldInput());
        $this->put('/admin/settings/password', ['current_password' => 'current-password', 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'])->assertRedirect('/admin/settings?tab=password')->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('new-password-123', $admin->fresh()->password));
        $this->assertAuthenticatedAs($admin);
        $this->post('/logout');
        $this->post('/login', ['email' => $admin->email, 'password' => 'current-password'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $admin->email, 'password' => 'new-password-123'])->assertRedirect('/admin');
    }

    public function test_email_credentials_are_encrypted_preserved_and_used_in_mail_configuration(): void
    {
        $this->actingAs($this->administrator());
        $this->put('/admin/settings/email', $this->emailData())->assertRedirect('/admin/settings?tab=email')->assertSessionHasNoErrors();
        $encrypted = DB::table('site_settings')->where('key', 'email.password')->value('value');
        $this->assertNotSame('mail-secret', $encrypted);
        $this->assertSame('mail-secret', Crypt::decryptString($encrypted));
        $this->get('/admin/settings?tab=email')->assertOk()->assertDontSee('mail-secret')->assertDontSee($encrypted);
        $data = array_replace($this->emailData(), ['smtp_password' => '']);
        $this->put('/admin/settings/email', $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($encrypted, DB::table('site_settings')->where('key', 'email.password')->value('value'));
        app(CommunicationSettings::class)->applyMailConfiguration();
        $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertSame('mail-secret', config('mail.mailers.smtp.password'));
        $this->assertTrue(config('mail.mailers.smtp.require_tls'));
        $this->assertSame('admin@example.com', app(CommunicationSettings::class)->value('email.notification_email'));
        Http::assertNothingSent();
    }

    public function test_invalid_email_configuration_does_not_flash_credentials(): void
    {
        $this->actingAs($this->administrator())->from('/admin/settings?tab=email')->put('/admin/settings/email', array_replace($this->emailData(), ['port' => 99999, 'from_address' => 'invalid']))->assertSessionHasErrors(['port', 'from_address']);
        $this->assertArrayNotHasKey('smtp_password', session()->getOldInput());
        $this->assertDatabaseMissing('site_settings', ['key' => 'email.password']);
    }

    public function test_sms_fields_are_saved_with_an_encrypted_key_and_do_not_send_requests(): void
    {
        $this->actingAs($this->administrator());
        $this->put('/admin/settings/sms', $this->smsData())->assertRedirect('/admin/settings?tab=sms')->assertSessionHasNoErrors();
        $encrypted = DB::table('site_settings')->where('key', 'sms.authentication_key')->value('value');
        $this->assertSame('sms-secret', Crypt::decryptString($encrypted));
        $this->assertNotSame('sms-secret', $encrypted);
        $this->assertDatabaseHas('site_settings', ['key' => 'sms.sender_id', 'value' => 'HOWVNT']);
        $this->assertDatabaseHas('site_settings', ['key' => 'sms.base_url', 'value' => 'https://sms.example.com/api']);
        $this->get('/admin/settings?tab=sms')->assertOk()->assertSee('Authentication Key')->assertSee('Sender ID')->assertSee('Base URL')->assertDontSee('sms-secret')->assertDontSee($encrypted)->assertDontSee('Account SID')->assertDontSee('name="provider"', false);
        $this->put('/admin/settings/sms', array_replace($this->smsData(), ['sms_authentication_key' => '']))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($encrypted, DB::table('site_settings')->where('key', 'sms.authentication_key')->value('value'));
        app(CommunicationSettings::class)->notifyEnquiry(Enquiry::factory()->create());
        Http::assertNothingSent();
    }

    public function test_sms_fields_validate_https_and_do_not_flash_the_authentication_key(): void
    {
        $this->actingAs($this->administrator())->from('/admin/settings?tab=sms')->put('/admin/settings/sms', array_replace($this->smsData(), ['base_url' => 'http://sms.example.com', 'sender_id' => '']))->assertSessionHasErrors(['base_url', 'sender_id']);
        $this->assertArrayNotHasKey('sms_authentication_key', session()->getOldInput());
        $this->assertDatabaseMissing('site_settings', ['key' => 'sms.authentication_key']);
        $this->put('/admin/settings/sms', array_replace($this->smsData(), ['sms_authentication_key' => '']))->assertSessionHasErrors('sms_authentication_key');
    }

    public function test_merged_general_form_saves_contact_and_branding_atomically(): void
    {
        $this->actingAs($this->administrator());
        $data = array_replace(config('website.branding'), ['_tab' => 'general', '_include_branding' => 1, 'contact_email' => 'hello@example.com', 'contact_phone' => '+911234567890', 'contact_location' => 'Howrah', 'tagline' => 'Our new tagline']);
        $this->put('/admin/settings', $data)->assertRedirect('/admin/settings?tab=general')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('site_settings', ['key' => 'contact_email', 'value' => 'hello@example.com']);
        $this->assertDatabaseHas('site_settings', ['key' => 'branding.tagline', 'value' => 'Our new tagline']);
        $this->put('/admin/settings', array_replace($data, ['contact_email' => 'other@example.com', 'header_button_url' => 'javascript:alert(1)']))->assertSessionHasErrors('header_button_url');
        $this->assertDatabaseHas('site_settings', ['key' => 'contact_email', 'value' => 'hello@example.com']);
    }

    public function test_nonadministrators_cannot_change_password_or_notification_settings(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        foreach (['/admin/settings/password', '/admin/settings/email', '/admin/settings/sms', '/admin/settings/branding'] as $url) {
            $this->put($url, [])->assertForbidden();
        }
    }
}

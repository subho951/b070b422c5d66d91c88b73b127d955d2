<?php

namespace Tests\Feature;

use App\Models\Enquiry;
use App\Models\User;
use Database\Seeders\WebsiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadSourceReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_and_export_require_admin_access(): void
    {
        $this->get('/admin/enquiries/sources?format=csv')->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get('/admin/enquiries/sources')->assertForbidden();
    }

    public function test_report_and_csv_share_the_same_date_and_source_filters(): void
    {
        $this->withoutVite();
        $this->seed(WebsiteSeeder::class);
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'administrator'])->save();
        $data = ['name' => 'Avery Smith', 'email' => 'private@example.com', 'description' => 'A private enquiry message', 'project_type' => '', 'company' => '', 'country' => '', 'privacy_consent' => true];
        $matches = Enquiry::create($data + [
            'lead_source' => 'gbp',
            'first_touch_attribution' => ['source' => 'facebook', 'utm_term' => '=1+1'],
            'last_touch_attribution' => ['source' => 'gbp', 'utm_campaign' => 'gbp'],
        ]);
        $matches->forceFill(['created_at' => '2026-07-15 23:59:59'])->save();
        $this->createEnquiry($data, 'gbp', '2026-07-16 00:00:00');
        $this->createEnquiry($data, 'facebook', '2026-07-15 12:00:00');
        $this->createEnquiry($data, null, '2026-07-15 12:30:00');

        $this->actingAs($admin)->get('/admin/enquiries/sources?from=2026-07-15&to=2026-07-15')
            ->assertOk()->assertViewHas('metrics', [
                'Total enquiries' => 3, 'Google Business Profile enquiries' => 1,
                'Social media enquiries' => 1, 'Organic search enquiries' => 0,
            ]);

        $filters = '/admin/enquiries/sources?from=2026-07-15&to=2026-07-15&source=gbp';
        $this->get($filters)->assertOk()->assertViewHas('enquiries', fn ($result): bool => $result->total() === 1);
        $csv = $this->get($filters.'&format=csv')->assertOk()->assertDownload('lead-sources.csv')->streamedContent();
        $rows = explode("\n", trim($csv));
        $this->assertCount(2, $rows);
        $this->assertSame([(string) $matches->id, '2026-07-15 23:59:59', 'Facebook', 'Google Business Profile', '', '', '', '', "'=1+1", '', '', 'gbp', '', ''], str_getcsv($rows[1], escape: ''));
        $this->assertStringNotContainsString('private@example.com', $csv);
    }

    private function createEnquiry(array $data, ?string $source, string $receivedAt): void
    {
        Enquiry::create($data + ['lead_source' => $source])->forceFill(['created_at' => $receivedAt])->save();
    }
}

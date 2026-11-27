<?php

namespace Tests\Feature;

use App\Models\Enquiry;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Database\Seeders\FeaturedProjectSeeder;
use Database\Seeders\WebsiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WebsiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(WebsiteSeeder::class);
    }

    private function enquiry(): array
    {
        return ['name' => 'Avery Smith', 'email' => 'avery@example.com', 'company' => 'Example Ltd', 'country' => 'United Kingdom', 'phone' => '+44 7700 900123', 'project_type' => 'custom-software', 'description' => 'We want to build a secure operations platform for our team.', 'privacy_consent' => '1'];
    }

    public function test_public_pages_render(): void
    {
        foreach (['/', '/about', '/testimonials', '/services', '/portfolio', '/engagement', '/contact', '/privacy', '/terms', '/login', '/portfolio/alfa-network', '/portfolio/e-school'] as $url) {
            $this->get($url)->assertOk();
        }
        foreach (array_keys(config('howvanta.services')) as $slug) {
            $this->get('/services/'.$slug)->assertOk();
        }
        $this->get('/subhomoy-samanta')->assertOk()
            ->assertSee('Subhomoy Samanta')->assertSee('15+ years')
            ->assertSee(route('portfolio.show', 'alfa-network'))
            ->assertSee(route('portfolio.show', 'e-school'))
            ->assertSee(route('contact', ['intent' => 'consultation']).'#enquiry');
        $this->get('/about')->assertSee(route('founder'));
        $this->get('/sitemap.xml')->assertSee(route('founder'));
        $this->get('/missing')->assertNotFound()->assertSee('back on track');
        $this->get('/services/unknown')->assertNotFound();
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')->assertSee('/services/custom-software');
    }

    public function test_production_canonical_and_sitemap_use_the_configured_origin(): void
    {
        $this->app->instance('env', 'production');
        config(['app.url' => 'https://howvanta.com']);
        $this->app->register(AppServiceProvider::class, force: true);

        $this->get('http://www.howvanta.com/about?source=example')
            ->assertOk()
            ->assertHeaderMissing('X-Robots-Tag')
            ->assertSee('<link rel="canonical" href="https://howvanta.com/about">', false);

        $this->get('http://www.howvanta.com/sitemap.xml')
            ->assertOk()
            ->assertSee('<loc>https://howvanta.com/about</loc>', false)
            ->assertDontSee('www.howvanta.com')
            ->assertDontSee('/about-us');
    }

    public function test_legacy_page_aliases_redirect_permanently(): void
    {
        foreach (['/about-us' => 'about', '/contact-us' => 'contact', '/solutions' => 'services'] as $alias => $destination) {
            $this->get($alias)->assertStatus(301)->assertRedirectToRoute($destination);
        }
    }

    public function test_homepage_selection_is_independent_from_exclusive_portfolio_ordering(): void
    {
        DB::table('portfolio_projects')->insert([
            'title' => 'Another client project', 'slug' => 'another-client-project', 'description' => 'A published project.',
            'status' => 'published', 'published_at' => now(), 'sort_order' => 0,
        ]);
        $this->get('/')->assertOk()->assertSee('ALFA Network')->assertSee('E School')
            ->assertSee('EXCLUSIVE PROJECT')->assertDontSee('Another client project');
        $this->get('/portfolio')->assertOk()->assertSeeInOrder(['<h3>ALFA Network</h3>', '<h3>E School</h3>', '<h3>Another client project</h3>'], false);
        DB::table('portfolio_projects')->where('slug', 'another-client-project')->update(['is_exclusive' => true]);
        $this->get('/')->assertDontSee('Another client project');
        DB::table('portfolio_projects')->where('slug', 'alfa-network')->update(['status' => 'draft']);
        DB::table('portfolio_projects')->where('slug', 'e-school')->update(['published_at' => now()->addDay()]);
        $this->get('/')->assertDontSee('href="http://localhost/portfolio/alfa-network"', false)->assertDontSee('href="http://localhost/portfolio/e-school"', false)->assertDontSee('Another client project');
    }

    public function test_administrator_can_change_exclusive_status_without_changing_visibility(): void
    {
        $user = User::factory()->create();
        $user->role = 'administrator';
        $user->save();
        $record = DB::table('portfolio_projects')->where('slug', 'alfa-network')->first();
        $this->actingAs($user)->get('/admin/content/portfolio/'.$record->id.'/edit')->assertOk()->assertSee('name="is_exclusive"', false);
        $this->put('/admin/content/portfolio/'.$record->id, ['title' => $record->title, 'status' => 'published', 'is_exclusive' => '0'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('portfolio_projects', ['id' => $record->id, 'is_exclusive' => false, 'status' => 'published']);
        $this->get('/')->assertSee('href="http://localhost/portfolio/alfa-network"', false);
        $this->get('/portfolio')->assertSee('<h3>ALFA Network</h3>', false);
        $this->put('/admin/content/portfolio/'.$record->id, ['title' => $record->title, 'status' => 'published', 'is_exclusive' => '1'])->assertSessionHasNoErrors();
        $this->get('/')->assertSee('href="http://localhost/portfolio/alfa-network"', false);
        $this->put('/admin/content/portfolio/'.$record->id, ['title' => $record->title, 'status' => 'published', 'is_exclusive' => 'invalid'])->assertSessionHasErrors('is_exclusive');
    }

    public function test_homepage_selection_can_be_changed_in_the_project_editor(): void
    {
        $user = User::factory()->create();
        $user->role = 'administrator';
        $user->save();
        $record = DB::table('portfolio_projects')->where('slug', 'alfa-network')->first();
        $this->actingAs($user)->get('/admin/content/portfolio/create')->assertOk()->assertSee('name="show_on_homepage"', false);
        $this->get('/admin/content/portfolio/'.$record->id.'/edit')->assertOk()->assertSee('Show on homepage:');
        foreach ([0, 1] as $selected) {
            $this->put('/admin/content/portfolio/'.$record->id, ['title' => $record->title, 'status' => 'published', 'show_on_homepage' => $selected])->assertSessionHasNoErrors();
            $this->assertDatabaseHas('portfolio_projects', ['id' => $record->id, 'show_on_homepage' => $selected, 'is_exclusive' => true]);
        }
        $this->put('/admin/content/portfolio/'.$record->id, ['title' => $record->title, 'status' => 'published', 'show_on_homepage' => 'invalid'])->assertSessionHasErrors('show_on_homepage');
    }

    public function test_featured_client_projects_have_screenshots_details_and_live_links(): void
    {
        $this->get('/')->assertOk()->assertSee('ALFA Network')->assertSee('E School')
            ->assertSee(config('featured-projects.alfa-network.alt'))->assertSee(config('featured-projects.e-school.alt'))
            ->assertDontSee('A connected operations platform')->assertDontSee('Knowledge, made accessible')
            ->assertDontSee('demonstration concepts');
        $portfolio = $this->get('/portfolio')->assertOk()->assertSee('View Case Study');
        foreach (config('featured-projects') as $slug => $project) {
            $portfolio->assertSee(route('portfolio.show', $slug))
                ->assertSee($project['category'])->assertSee($project['description']);
            $this->get('/portfolio/'.$slug)->assertOk()->assertSee($project['url'], false)
                ->assertSee($project['alt'])->assertSee($project['title'])
                ->assertSeeInOrder([$project['challenge'], 'Howvanta’s role', $project['content'], $project['solution'], $project['outcome'], 'Discuss a Similar Project'])
                ->assertSee(route('contact', ['intent' => 'consultation', 'service' => config('portfolio-catalog.service_links.'.$slug)]).'#enquiry')
                ->assertDontSee('project-disclaimer', false);
        }
        DB::table('portfolio_projects')->where('slug', 'alfa-network')->update(['title' => 'Edited project']);
        $this->seed(FeaturedProjectSeeder::class);
        $this->assertDatabaseHas('portfolio_projects', ['slug' => 'alfa-network', 'title' => 'Edited project']);
        $this->assertDatabaseCount('portfolio_projects', 2);
    }

    public function test_enquiry_is_stored_and_sanitized(): void
    {
        config(['mail.default' => 'array', 'howvanta.notification_email' => 'qa-inbox@example.com']);
        $data = $this->enquiry();
        $data['name'] = '<b>Avery Smith</b>';
        $this->post('/contact', $data)->assertRedirect('/contact/thank-you')->assertSessionHas('success');
        $this->assertDatabaseHas('enquiries', ['name' => 'Avery Smith', 'email' => 'avery@example.com', 'status' => 'new', 'privacy_consent' => true]);
        $this->actingAs(User::factory()->create(['role' => 'administrator']))->get('/admin')
            ->assertOk()->assertSee('Avery Smith')->assertSee('avery@example.com');

        $sent = Mail::mailer()->getSymfonyTransport()->messages()->sole()->getOriginalMessage();
        $this->assertSame('qa-inbox@example.com', $sent->getTo()[0]->getAddress());
        $this->assertSame('New Howvanta enquiry from Avery Smith <avery@example.com>', $sent->getSubject());
        foreach (['Name: Avery Smith', 'Email: avery@example.com', 'Phone: +44 7700 900123', 'Company: Example Ltd', 'Country: United Kingdom', 'Budget: Not specified', 'Privacy consent: Given', $data['description'], route('admin.enquiries.show', Enquiry::sole())] as $detail) {
            $this->assertStringContainsString($detail, $sent->getTextBody());
        }
    }

    public function test_business_map_only_loads_on_contact_after_expansion(): void
    {
        $this->get('/')->assertOk()->assertDontSee('data-location-map', false);
        $this->get('/contact')->assertOk()
            ->assertSee('data-location-map', false)
            ->assertSee('data-src="https://www.google.com/maps?', false)
            ->assertDontSee(' src="https://www.google.com/maps?', false);
    }

    public function test_portfolio_redirects_legacy_urls_and_filters_projects_by_industry(): void
    {
        DB::table('portfolio_projects')->insert([
            'slug' => 'mobile-shop-billing', 'title' => 'Mobile shop billing', 'category' => 'Software',
            'description' => 'A published retail project.', 'status' => 'published', 'published_at' => now(),
        ]);

        $this->get('/portfolio/legacy-project-1-stock-billing-software-of-mobile-shop')
            ->assertStatus(301)->assertRedirect('/portfolio/mobile-shop-billing');
        $this->get('/portfolio?industry=Retail')->assertOk()->assertSee('1 project')
            ->assertSee('Mobile shop billing')->assertDontSee('ALFA Network');
        $this->get('/sitemap.xml')->assertSee('/portfolio/mobile-shop-billing')->assertDontSee('legacy-project-1-');
    }

    public function test_invalid_enquiries_are_not_stored(): void
    {
        $data = $this->enquiry();
        $data['email'] = 'invalid';
        $data['privacy_consent'] = null;
        $data['project_type'] = 'unknown';
        $this->post('/contact', $data)->assertSessionHasErrors(['email', 'privacy_consent', 'project_type']);
        $this->assertDatabaseCount('enquiries', 0);
    }

    public function test_honeypot_and_untrusted_status_are_handled(): void
    {
        $data = $this->enquiry();
        $data['website'] = 'spam.example';
        $this->post('/contact', $data)->assertSessionHasErrors('website');
        unset($data['website']);
        $data['status'] = 'converted';
        $this->post('/contact', $data)->assertRedirect('/contact/thank-you');
        $this->assertDatabaseHas('enquiries', ['status' => 'new']);
    }

    public function test_contact_rate_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/contact', $this->enquiry())->assertRedirect();
        }
        $this->post('/contact', $this->enquiry())->assertStatus(429);
    }

    public function test_contact_clicks_are_counted_by_day_and_action(): void
    {
        $this->postJson(route('contact.click'), ['action' => 'email'])->assertNoContent();
        $this->postJson(route('contact.click'), ['action' => 'email'])->assertNoContent();
        $this->postJson(route('contact.click'), ['action' => 'booking'])->assertNoContent();
        $this->postJson(route('contact.click'), ['action' => 'proposal_sent'])->assertUnprocessable();

        $this->assertDatabaseHas('website_contact_clicks', ['day' => today()->toDateString(), 'action' => 'email', 'clicks' => 2]);
        $this->assertDatabaseHas('website_contact_clicks', ['day' => today()->toDateString(), 'action' => 'booking', 'clicks' => 1]);
        $this->assertDatabaseCount('website_contact_clicks', 2);
    }

    public function test_dashboard_requires_administrator(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $staff = User::factory()->create();
        $this->actingAs($staff)->get('/admin')->assertForbidden();
        $admin = User::factory()->create();
        $admin->role = 'administrator';
        $admin->save();
        $enquiry = Enquiry::create(array_merge($this->enquiry(), ['privacy_consent' => true, 'intent' => 'support']));
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Avery Smith')->assertSee('Ask About Support');
        $this->patch(route('admin.enquiries.update', $enquiry), ['status' => 'new', 'assigned_to' => $staff->id])
            ->assertSessionHasErrors('assigned_to');
        $this->patch(route('admin.enquiries.update', $enquiry), ['status' => 'qualified', 'assigned_to' => $admin->id, 'consultation_booked' => true, 'proposal_sent' => true])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('enquiries', ['id' => $enquiry->id, 'status' => 'qualified', 'assigned_to' => $admin->id]);
        $enquiry->refresh();
        $this->assertNotNull($enquiry->qualified_at);
        $this->assertNotNull($enquiry->consultation_booked_at);
        $this->assertNotNull($enquiry->proposal_sent_at);
        $this->patch(route('admin.enquiries.update', $enquiry), ['status' => 'converted', 'consultation_booked' => false])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('enquiries', ['id' => $enquiry->id, 'qualified_at' => $enquiry->qualified_at, 'consultation_booked_at' => null, 'proposal_sent_at' => $enquiry->proposal_sent_at]);
    }

    public function test_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => bcrypt('a-long-test-password')]);
        $user->role = 'administrator';
        $user->save();
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/login', ['email' => $user->email, 'password' => 'a-long-test-password'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'bad'])->assertRedirect();
        }
        $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'bad'])->assertStatus(429);
    }

    public function test_enquiry_details_are_escaped(): void
    {
        $admin = User::factory()->create();
        $admin->role = 'administrator';
        $admin->save();
        $enquiry = Enquiry::factory()->create(['description' => '<script>alert(1)</script>']);

        $this->actingAs($admin)->get(route('admin.enquiries.show', $enquiry))
            ->assertOk()
            ->assertSee($enquiry->email)
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_administrator_can_browse_enquiries_from_the_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $older = Enquiry::factory()->create(['name' => 'Older Enquiry', 'created_at' => now()->subDay()]);
        Enquiry::factory()->count(14)->create();
        $newest = Enquiry::factory()->create(['name' => 'Newest Enquiry']);
        $deleted = Enquiry::factory()->create(['name' => 'Deleted Enquiry']);
        $deleted->delete();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertSee(route('admin.enquiries.index'));
        $this->get(route('admin.enquiries.index'))->assertOk()
            ->assertSee('Newest Enquiry')
            ->assertSee(route('admin.enquiries.show', $newest))
            ->assertDontSee('Older Enquiry')
            ->assertDontSee('Deleted Enquiry');
        $this->get(route('admin.enquiries.index', ['page' => 2]))->assertOk()
            ->assertSee(route('admin.enquiries.show', $older));
        $this->get(route('admin.enquiries.show', $deleted))->assertNotFound();
    }

    public function test_enquiry_pages_require_an_administrator(): void
    {
        $enquiry = Enquiry::factory()->create();
        $staff = User::factory()->create(['role' => 'staff']);

        $this->get(route('admin.enquiries.index'))->assertRedirect(route('login'));
        $this->get(route('admin.enquiries.show', $enquiry))->assertRedirect(route('login'));
        $this->actingAs($staff)->get(route('admin.enquiries.index'))->assertForbidden();
        $this->get(route('admin.enquiries.show', $enquiry))->assertForbidden();
    }
}

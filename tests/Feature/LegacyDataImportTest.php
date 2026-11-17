<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\LegacyDataImporter;
use Database\Seeders\WebsiteSeeder;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegacyDataImportTest extends TestCase
{
    use RefreshDatabase;

    private Connection $source;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(WebsiteSeeder::class);
        Storage::fake('local');
        Storage::fake('public');
        config(['database.connections.legacy_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
        DB::purge('legacy_test');
        $this->source = DB::connection('legacy_test');
        foreach ([
            'td_customer' => 'customer_id INTEGER PRIMARY KEY, site_id INTEGER, customer_name TEXT, customer_email TEXT, customer_phone TEXT, billing_address TEXT, billing_gst TEXT, opening_balance REAL, created TEXT, published INTEGER, domain_password TEXT',
            'td_bill' => 'bill_id INTEGER PRIMARY KEY, site_id INTEGER, bill_no TEXT, customer_id INTEGER, bill_date TEXT, taxable_amt REAL, discount_amt REAL, total_gst REAL, grand_total REAL',
            'td_customer_payment' => 'customer_payment_id INTEGER PRIMARY KEY, customer_id INTEGER, site_id INTEGER, payment_type TEXT, transaction_amount REAL, transaction_date TEXT, bill_no TEXT, payment_mode TEXT, txn_number TEXT, remain_balance REAL',
            'td_bill_details' => 'bill_detail_id INTEGER PRIMARY KEY, bill_id INTEGER, product_id INTEGER, qty REAL, rate REAL, actual_amt REAL, cgst_amt REAL, sgst_amt REAL, igst_amt REAL, subtotal REAL',
            'td_site_settings' => 'site_id INTEGER PRIMARY KEY, site_name TEXT, site_email_address TEXT, site_phone TEXT, site_address TEXT, site_gstn TEXT, site_pan TEXT, site_state_code TEXT, site_logo TEXT',
            'td_users' => 'id INTEGER PRIMARY KEY, user_type TEXT, name TEXT, username TEXT, email TEXT, password TEXT, password_original TEXT, published INTEGER',
            'td_login_log' => 'log_id INTEGER PRIMARY KEY, user_id INTEGER, ip_address TEXT, last_login TEXT, last_browser_used TEXT',
            'td_services' => 'service_id INTEGER PRIMARY KEY, title TEXT, description TEXT, published INTEGER',
            'td_testimonials' => 'testimonial_id INTEGER PRIMARY KEY, name TEXT, company_name TEXT, description TEXT, published INTEGER',
            'ci_sessions' => 'id TEXT PRIMARY KEY, data TEXT',
        ] as $table => $columns) {
            $this->source->statement('CREATE TABLE '.$table.' ('.$columns.')');
        }
        $this->source->table('td_customer')->insert(['customer_id' => 1, 'site_id' => 1, 'customer_name' => 'Legacy Customer', 'customer_email' => 'customer@example.com', 'customer_phone' => '1234567890', 'billing_address' => 'Customer address', 'billing_gst' => 'TAX-1', 'opening_balance' => 50, 'created' => '2020-07-01', 'published' => 1, 'domain_password' => 'sensitive-domain-password']);
        $this->source->table('td_site_settings')->insert(['site_id' => 1, 'site_name' => 'Legacy Company', 'site_email_address' => 'company@example.com', 'site_phone' => '1234567890', 'site_address' => 'Seller address', 'site_gstn' => 'GST-1', 'site_pan' => '', 'site_state_code' => '19', 'site_logo' => '']);
        $this->source->table('td_bill')->insert(['bill_id' => 1, 'site_id' => 1, 'bill_no' => 'OLD/001', 'customer_id' => 1, 'bill_date' => '2020-07-01', 'taxable_amt' => 110, 'discount_amt' => 10, 'total_gst' => 0, 'grand_total' => 100]);
        $this->source->table('td_bill_details')->insert(['bill_detail_id' => 1, 'bill_id' => 1, 'product_id' => 1, 'qty' => 1, 'rate' => 110, 'actual_amt' => 110, 'cgst_amt' => 0, 'sgst_amt' => 0, 'igst_amt' => 0, 'subtotal' => 110]);
        $this->source->table('td_customer_payment')->insert([
            ['customer_payment_id' => 1, 'customer_id' => 1, 'site_id' => 1, 'payment_type' => 'in', 'transaction_amount' => 0, 'transaction_date' => '2020-07-01', 'bill_no' => '', 'payment_mode' => '', 'remain_balance' => 0],
            ['customer_payment_id' => 2, 'customer_id' => 1, 'site_id' => 1, 'payment_type' => 'in', 'transaction_amount' => 100, 'transaction_date' => '2020-07-01', 'bill_no' => 'OLD/001', 'payment_mode' => 'NA', 'remain_balance' => 100],
            ['customer_payment_id' => 3, 'customer_id' => 1, 'site_id' => 1, 'payment_type' => 'out', 'transaction_amount' => 50, 'transaction_date' => '2020-07-02', 'bill_no' => '', 'payment_mode' => 'UPI', 'remain_balance' => 50],
        ]);
        $this->source->table('ci_sessions')->insert(['id' => 'old-session', 'data' => 'expired-session-secret']);
    }

    protected function tearDown(): void
    {
        DB::purge('legacy_test');
        parent::tearDown();
    }

    /** @return array<string, mixed> */
    private function runImport(bool $dryRun = false): array
    {
        return app(LegacyDataImporter::class)->run($this->source, '/missing-legacy-files', $dryRun);
    }

    public function test_dry_run_reads_counts_without_mutating_destination_or_source(): void
    {
        $summary = $this->runImport(true);
        $this->assertTrue($summary['dry_run']);
        $this->assertSame(3, $summary['source_counts']['td_customer_payment']);
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('legacy_import_records', 0);
        $this->assertSame(3, $this->source->table('td_customer_payment')->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_import_preserves_invoice_totals_and_original_ledger_without_turning_charges_into_wallet_funds(): void
    {
        $summary = $this->runImport();
        $this->assertDatabaseHas('invoices', ['invoice_number' => 'OLD/001', 'subtotal' => 110, 'discount_total' => 10, 'grand_total' => 100, 'status' => 'issued', 'currency' => 'INR']);
        $this->assertDatabaseCount('invoice_items', 1);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('wallet_entries', 1);
        $this->assertDatabaseHas('wallet_entries', ['direction' => 'credit', 'amount' => 50, 'reference' => 'LEGACY-RECEIPT-3']);
        $this->assertDatabaseCount('legacy_customer_transactions', 3);
        $this->assertDatabaseHas('legacy_customer_transactions', ['type' => 'invoice_charge', 'amount' => 100, 'balance_due' => 100]);
        $this->assertDatabaseHas('legacy_customer_transactions', ['type' => 'receipt', 'amount' => 50, 'balance_due' => 50]);
        $archive = DB::table('legacy_import_records')->where('source_table', 'td_customer')->first();
        $this->assertStringNotContainsString('sensitive-domain-password', $archive->encrypted_payload);
        $this->assertStringContainsString('sensitive-domain-password', Crypt::decryptString($archive->encrypted_payload));
        $this->assertDatabaseMissing('legacy_import_records', ['source_table' => 'ci_sessions']);
        Storage::disk('local')->assertExists($summary['backup']);
        Storage::disk('local')->assertExists($summary['report']);
        $backup = json_decode(Crypt::decryptString(Storage::disk('local')->get($summary['backup'])), true);
        $this->assertSame([], $backup['customers']);
        $this->assertSame(1, $this->source->table('td_bill')->count());
        $user = User::factory()->create();
        $user->role = 'administrator';
        $user->save();
        $this->actingAs($user)->get(route('admin.wallet.show', DB::table('customers')->value('id')))->assertOk()->assertSee('Original legacy account statement');
        $this->get(route('admin.invoices.show', DB::table('invoices')->value('id')))->assertOk()->assertSee('OLD/001');
    }

    public function test_rerun_does_not_duplicate_data_or_overwrite_edits_and_wallet_allocations(): void
    {
        $this->runImport();
        $counts = [];
        foreach (['customers', 'invoices', 'invoice_items', 'wallet_entries', 'office_records', 'legacy_import_records', 'legacy_customer_transactions'] as $table) {
            $counts[$table] = DB::table($table)->count();
        }
        DB::table('customers')->update(['name' => 'Edited Customer']);
        DB::table('invoices')->update(['notes' => 'Edited notes']);
        $result = $this->runImport();
        foreach ($counts as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }
        $this->assertSame(0, $result['created_target_records']);
        $this->assertDatabaseHas('customers', ['name' => 'Edited Customer']);
        $this->assertDatabaseHas('invoices', ['notes' => 'Edited notes']);
    }

    public function test_missing_customers_duplicate_emails_and_invoice_numbers_are_preserved_without_merging(): void
    {
        DB::table('customers')->insert(['name' => 'Existing Customer', 'email' => 'customer@example.com', 'country' => 'India']);
        $this->source->table('td_customer')->insert(['customer_id' => 2, 'site_id' => 1, 'customer_name' => 'Missing Email', 'customer_email' => '', 'published' => 1]);
        $this->source->table('td_bill')->insert(['bill_id' => 2, 'site_id' => 1, 'bill_no' => 'OLD/001', 'customer_id' => 99, 'bill_date' => '2020-07-02', 'taxable_amt' => 10, 'discount_amt' => 0, 'total_gst' => 0, 'grand_total' => 10]);
        $summary = $this->runImport();
        $this->assertDatabaseCount('customers', 4);
        $this->assertDatabaseHas('customers', ['name' => 'Existing Customer', 'email' => 'customer@example.com']);
        $this->assertDatabaseHas('customers', ['name' => 'Legacy customer #99 (source record missing)', 'status' => 'inactive']);
        $this->assertDatabaseHas('invoices', ['invoice_number' => 'OLD/001-LEGACY-2', 'grand_total' => 10]);
        $this->assertStringContainsString('missing legacy customer #99', implode(' ', $summary['warnings']));
        $this->assertStringContainsString('duplicate invoice number', implode(' ', $summary['warnings']));
        $this->assertStringContainsString('missing, invalid or duplicate email', implode(' ', $summary['warnings']));
    }

    public function test_linked_receipts_are_imported_as_payments_and_legacy_content_is_available_as_drafts(): void
    {
        $this->source->table('td_customer_payment')->where('customer_payment_id', 3)->update(['bill_no' => 'OLD/001']);
        $this->source->table('td_services')->insert(['service_id' => 1, 'title' => 'Legacy service', 'description' => '<p>Source description</p>', 'published' => 1]);
        $this->source->table('td_testimonials')->insert(['testimonial_id' => 1, 'name' => 'Legacy Client', 'company_name' => 'Client Company', 'description' => 'Original feedback', 'published' => 1]);
        $this->source->table('td_users')->insert(['id' => 3, 'user_type' => 'MA', 'name' => 'Legacy Admin', 'username' => 'legacy-admin', 'email' => 'legacy-admin@example.com', 'password' => md5('original-password'), 'password_original' => 'original-password', 'published' => 1]);
        $this->source->table('td_login_log')->insert(['log_id' => 1, 'user_id' => 3, 'ip_address' => '127.0.0.1', 'last_login' => '2020-07-02', 'last_browser_used' => 'Browser']);
        $this->runImport();
        $this->assertDatabaseCount('wallet_entries', 0);
        $this->assertDatabaseHas('payments', ['amount' => 50, 'method' => 'upi']);
        $this->assertDatabaseHas('invoices', ['status' => 'partially_paid']);
        $this->assertDatabaseHas('services', ['title' => 'Legacy service', 'status' => 'draft']);
        $this->assertDatabaseHas('testimonials', ['name' => 'Legacy Client', 'status' => 'draft', 'consent_confirmed' => false]);
        $this->assertTrue(Hash::check('original-password', User::where('email', 'legacy-admin@example.com')->value('password')));
        $this->assertDatabaseHas('activity_logs', ['event' => 'login', 'actor_name' => 'Legacy Admin']);
        $this->runImport();
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_failed_import_rolls_back_all_destination_changes_and_preserves_source(): void
    {
        DB::table('currencies')->where('code', 'INR')->delete();
        try {
            $this->runImport();
            $this->fail('Import should fail when its required currency is missing.');
        } catch (QueryException) {
            $this->assertDatabaseCount('customers', 0);
            $this->assertDatabaseCount('invoices', 0);
            $this->assertDatabaseCount('legacy_import_records', 0);
            $this->assertSame(3, $this->source->table('td_customer_payment')->count());
            $this->assertCount(1, Storage::disk('local')->allFiles('legacy-imports'));
        }
    }
}

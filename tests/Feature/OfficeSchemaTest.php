<?php

namespace Tests\Feature;

use App\Models\Enquiry;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OfficeSchemaTest extends TestCase
{
    use RefreshDatabase;

    private function customer(string $email = 'billing@example.com'): int
    {
        return DB::table('customers')->insertGetId(['name' => 'Example Company', 'email' => $email, 'country' => 'India']);
    }

    private function invoice(int $customerId, array $overrides = []): int
    {
        return DB::table('invoices')->insertGetId(array_merge([
            'invoice_number' => 'INV-000001', 'customer_id' => $customerId,
            'issue_date' => '2026-10-13', 'due_date' => '2026-11-13', 'currency' => 'KWD', 'currency_decimal_places' => 3,
            'customer_name' => 'Example Company', 'customer_email' => 'billing@example.com',
            'billing_address' => 'Example billing address', 'billing_country' => 'India',
            'seller_name' => 'Howvanta Technologies', 'seller_address' => 'Howrah, India', 'grand_total' => '100.125',
        ], $overrides));
    }

    private function payment(int $invoiceId, string $currency = 'KWD', string $reference = 'PAY-000001'): void
    {
        DB::table('payments')->insert([
            'invoice_id' => $invoiceId, 'currency' => $currency, 'reference' => $reference,
            'amount' => '25.125', 'payment_date' => '2026-10-13', 'method' => 'bank_transfer',
        ]);
    }

    public function test_invoice_cannot_reference_another_customers_project(): void
    {
        $customerId = $this->customer();
        $otherCustomerId = $this->customer('other@example.com');
        $projectId = DB::table('projects')->insertGetId(['reference' => 'PRJ-001', 'title' => 'Example project', 'customer_id' => $otherCustomerId, 'currency' => 'KWD']);

        $this->expectException(QueryException::class);
        $this->invoice($customerId, ['project_id' => $projectId]);
    }

    public function test_payment_currency_must_match_the_invoice(): void
    {
        $invoiceId = $this->invoice($this->customer());

        $this->expectException(QueryException::class);
        $this->payment($invoiceId, 'USD');
    }

    public function test_invoice_number_cannot_be_reused(): void
    {
        $customerId = $this->customer();
        $this->invoice($customerId);

        $this->expectException(QueryException::class);
        $this->invoice($customerId);
    }

    public function test_invoice_with_payments_cannot_be_deleted(): void
    {
        $invoiceId = $this->invoice($this->customer());
        $this->payment($invoiceId);

        $this->expectException(QueryException::class);
        DB::table('invoices')->where('id', $invoiceId)->delete();
    }

    public function test_partial_payments_are_separate_and_customer_changes_preserve_invoice_snapshot(): void
    {
        $customerId = $this->customer();
        $invoiceId = $this->invoice($customerId);
        $this->payment($invoiceId);
        $this->payment($invoiceId, 'KWD', 'PAY-000002');

        DB::table('customers')->where('id', $customerId)->update(['name' => 'Renamed Company', 'billing_currency' => 'USD']);

        $this->assertDatabaseCount('payments', 2);
        $this->assertDatabaseHas('invoices', ['id' => $invoiceId, 'customer_name' => 'Example Company', 'currency' => 'KWD', 'currency_decimal_places' => 3, 'grand_total' => '100.125']);
        $this->assertDatabaseHas('currencies', ['code' => 'KWD', 'decimal_places' => 3]);
        $this->assertDatabaseHas('currencies', ['code' => 'INR', 'decimal_places' => 2]);
    }

    public function test_staff_permissions_default_to_no_access_and_cannot_be_duplicated(): void
    {
        $user = User::factory()->create();
        DB::table('user_permissions')->insert(['user_id' => $user->id, 'module' => 'billing']);
        $this->assertDatabaseHas('user_permissions', ['user_id' => $user->id, 'module' => 'billing', 'can_view' => false, 'can_create' => false, 'can_update' => false, 'can_delete' => false]);

        $this->expectException(QueryException::class);
        DB::table('user_permissions')->insert(['user_id' => $user->id, 'module' => 'billing']);
    }

    public function test_new_migrations_can_roll_back_and_reapply_without_removing_existing_enquiries(): void
    {
        $enquiry = Enquiry::factory()->create();

        $this->assertSame(0, Artisan::call('migrate:rollback', ['--step' => 5, '--force' => true]));
        $this->assertDatabaseHas('enquiries', ['id' => $enquiry->id]);
        $this->assertSame(0, Artisan::call('migrate', ['--force' => true]));

        $this->assertDatabaseHas('enquiries', ['id' => $enquiry->id]);
        $this->assertDatabaseHas('currencies', ['code' => 'KWD', 'decimal_places' => 3]);
    }
}

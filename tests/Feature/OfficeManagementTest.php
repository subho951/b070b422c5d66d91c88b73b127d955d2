<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\WebsiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OfficeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(WebsiteSeeder::class);
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        $user->role = 'administrator';
        $user->save();

        return $user;
    }

    /** @return array<string, mixed> */
    private function customerData(): array
    {
        return ['name' => 'Example Customer', 'type' => 'company', 'email' => 'customer@example.com', 'country' => 'India', 'billing_currency' => 'INR', 'billing_address' => 'Customer address', 'status' => 'active'];
    }

    private function customer(): int
    {
        return DB::table('customers')->insertGetId($this->customerData());
    }

    /** @return array<string, mixed> */
    private function invoiceData(int $customer, string $currency = 'INR'): array
    {
        return ['customer_id' => $customer, 'currency' => $currency, 'issue_date' => '2026-11-14', 'due_date' => '2026-12-14', 'seller_name' => 'Seller Company', 'seller_address' => 'Seller address', 'items' => [['description' => 'Development', 'quantity' => 2, 'unit_price' => 100, 'discount_amount' => 20, 'tax_rate' => 10]]];
    }

    private function invoice(int $customer, string $currency = 'INR'): int
    {
        $this->post(route('admin.invoices.store'), $this->invoiceData($customer, $currency))->assertRedirect();

        return (int) DB::table('invoices')->max('id');
    }

    public function test_all_management_menus_and_forms_render_and_records_can_be_managed(): void
    {
        $this->actingAs($this->administrator());
        foreach (array_keys(config('office')) as $module) {
            $this->get(route('admin.office.index', $module))->assertOk();
            $this->get(route('admin.office.create', $module))->assertOk();
        }
        $samples = [
            'companies' => ['name' => 'Company', 'email' => 'company@example.com', 'address' => 'Company address'],
            'banks' => ['name' => 'Bank', 'account_holder' => 'Company', 'account_number' => '0123456'],
            'workflow' => ['name' => 'Discovery', 'description' => 'Understand requirements', 'sort_order' => 1],
            'project-categories' => ['name' => 'Web applications'],
            'team' => ['name' => 'Team Member', 'designation' => 'Engineer'],
            'clients' => ['name' => 'Client', 'website' => 'https://example.com'],
            'packages' => ['name' => 'Starter', 'amount' => 500, 'currency' => 'INR', 'description' => 'Starter service'],
        ];
        foreach ($samples as $module => $data) {
            $this->post(route('admin.office.store', $module), $data)->assertSessionHasNoErrors()->assertRedirect();
            $id = DB::table('office_records')->where('module', $module)->value('id');
            $this->get(route('admin.office.edit', [$module, $id]))->assertOk()->assertSee($data['name']);
            $this->put(route('admin.office.update', [$module, $id]), array_replace($data, ['name' => 'Updated '.$data['name']]))->assertSessionHasNoErrors();
            $this->delete(route('admin.office.destroy', [$module, $id]))->assertRedirect();
            $this->get(route('admin.office.edit', [$module, $id]))->assertNotFound();
        }
        foreach (['sales', 'expenses'] as $report) {
            $this->get(route('admin.reports.show', $report))->assertOk();
        }
        $this->get(route('admin.login-logs'))->assertOk();
        $this->get(route('admin.invoices.index'))->assertOk();
        $this->get(route('admin.invoices.create'))->assertOk();
        $this->get(route('admin.office.index', 'unknown'))->assertNotFound();
    }

    public function test_customer_crud_validation_and_financial_history_protection(): void
    {
        $this->actingAs($this->administrator());
        $this->post(route('admin.office.store', 'customers'), $this->customerData() + ['created_by' => 999])->assertSessionHasNoErrors();
        $id = (int) DB::table('customers')->value('id');
        $this->get(route('admin.office.edit', ['customers', $id]))->assertOk();
        $this->get(route('admin.wallet.show', $id))->assertOk();
        $this->post(route('admin.office.store', 'customers'), $this->customerData())->assertSessionHasErrors('email');
        $this->put(route('admin.office.update', ['customers', $id]), array_replace($this->customerData(), ['name' => 'Updated Customer']))->assertSessionHasNoErrors();
        $this->post(route('admin.wallet.store', $id), ['direction' => 'credit', 'amount' => 10, 'currency' => 'INR', 'transaction_date' => '2026-11-14', 'notes' => 'Opening balance'])->assertSessionHasNoErrors();
        $this->delete(route('admin.office.destroy', ['customers', $id]))->assertSessionHasErrors('record');
        $this->assertDatabaseHas('customers', ['id' => $id, 'name' => 'Updated Customer', 'deleted_at' => null, 'created_by' => null]);
    }

    public function test_invoice_calculates_totals_locks_snapshots_and_handles_wallet_payment_and_void(): void
    {
        $this->actingAs($this->administrator());
        $customer = $this->customer();
        $id = $this->invoice($customer);
        $this->assertDatabaseHas('invoices', ['id' => $id, 'invoice_number' => 'INV-000001', 'subtotal' => 200, 'discount_total' => 20, 'tax_total' => 18, 'grand_total' => 198, 'status' => 'draft']);
        $this->get(route('admin.invoices.edit', $id))->assertOk();
        $this->get(route('admin.invoices.show', $id))->assertOk()->assertSee('Seller Company');
        $payment = ['method' => 'wallet', 'amount' => 198, 'payment_date' => '2026-11-14'];
        $this->post(route('admin.invoices.payments.store', $id), $payment)->assertSessionHasErrors('amount');
        $this->patch(route('admin.invoices.status', $id), ['status' => 'issued'])->assertSessionHasNoErrors();
        $this->put(route('admin.invoices.update', $id), $this->invoiceData($customer))->assertForbidden();
        $this->get(route('admin.invoices.edit', $id))->assertForbidden();
        DB::table('customers')->where('id', $customer)->update(['name' => 'Renamed Customer']);
        $this->assertDatabaseHas('invoices', ['id' => $id, 'customer_name' => 'Example Customer']);
        $this->post(route('admin.invoices.payments.store', $id), $payment)->assertSessionHasErrors('amount');
        $this->post(route('admin.wallet.store', $customer), ['direction' => 'credit', 'amount' => 200, 'currency' => 'USD', 'transaction_date' => '2026-11-14', 'notes' => 'USD deposit'])->assertSessionHasNoErrors();
        $this->post(route('admin.invoices.payments.store', $id), $payment)->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('payments', 0);
        $this->post(route('admin.wallet.store', $customer), ['direction' => 'credit', 'amount' => 200, 'currency' => 'INR', 'transaction_date' => '2026-11-14', 'notes' => 'INR deposit'])->assertSessionHasNoErrors();
        $this->post(route('admin.invoices.payments.store', $id), $payment)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('invoices', ['id' => $id, 'status' => 'paid']);
        $this->assertDatabaseHas('wallet_entries', ['invoice_id' => $id, 'direction' => 'debit', 'amount' => 198, 'currency' => 'INR']);
        $this->post(route('admin.invoices.payments.store', $id), $payment)->assertSessionHasErrors('amount');
        $this->patch(route('admin.invoices.status', $id), ['status' => 'cancelled', 'reason' => 'Cancelled'])->assertSessionHasErrors('status');
        $paymentId = DB::table('payments')->value('id');
        $this->patch(route('admin.invoices.payments.void', [$id, $paymentId]), ['reason' => 'Correction'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('invoices', ['id' => $id, 'status' => 'issued']);
        $this->assertDatabaseHas('payments', ['id' => $paymentId, 'status' => 'voided']);
        $this->assertDatabaseHas('wallet_entries', ['invoice_id' => $id, 'direction' => 'credit', 'amount' => 198]);
        $this->patch(route('admin.invoices.payments.void', [$id, $paymentId]), ['reason' => 'Repeat'])->assertSessionHasErrors('payment');
        $this->patch(route('admin.invoices.status', $id), ['status' => 'cancelled', 'reason' => 'Cancelled'])->assertSessionHasNoErrors();
        $this->get(route('admin.invoices.show', $id))->assertOk()->assertSee('Cancelled');
    }

    public function test_partial_payments_overpayment_wallet_withdrawals_and_currency_precision(): void
    {
        $this->actingAs($this->administrator());
        $customer = $this->customer();
        $data = $this->invoiceData($customer, 'KWD');
        $data['items'] = [['description' => 'Precision', 'quantity' => 1, 'unit_price' => '100.125', 'discount_amount' => 0, 'tax_rate' => 0]];
        $this->post(route('admin.invoices.store'), $data)->assertSessionHasNoErrors();
        $id = (int) DB::table('invoices')->value('id');
        $this->patch(route('admin.invoices.status', $id), ['status' => 'issued'])->assertSessionHasNoErrors();
        $this->post(route('admin.invoices.payments.store', $id), ['amount' => '25.125', 'method' => 'bank_transfer', 'payment_date' => '2026-11-14'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('invoices', ['id' => $id, 'grand_total' => '100.125', 'status' => 'partially_paid']);
        $this->post(route('admin.invoices.payments.store', $id), ['amount' => '75.001', 'method' => 'cash', 'payment_date' => '2026-11-14'])->assertSessionHasErrors('amount');
        $this->post(route('admin.invoices.payments.store', $id), ['amount' => '75.000', 'method' => 'cash', 'payment_date' => '2026-11-14'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('invoices', ['id' => $id, 'status' => 'paid']);
        $this->post(route('admin.wallet.store', $customer), ['direction' => 'debit', 'amount' => 1, 'currency' => 'KWD', 'transaction_date' => '2026-11-14', 'notes' => 'Invalid withdrawal'])->assertSessionHasErrors('amount');
        $this->get(route('admin.invoices.show', $id))->assertOk()->assertSee('100.125');
    }

    public function test_invoice_validation_and_draft_editing(): void
    {
        $this->actingAs($this->administrator());
        $customer = $this->customer();
        $data = $this->invoiceData($customer);
        $data['items'][0]['discount_amount'] = 201;
        $this->post(route('admin.invoices.store'), $data)->assertSessionHasErrors('items.0.discount_amount');
        $this->assertDatabaseCount('invoices', 0);
        $id = $this->invoice($customer);
        $data = $this->invoiceData($customer);
        $data['items'][0]['quantity'] = 3;
        $this->put(route('admin.invoices.update', $id), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('invoice_items', 1);
        $this->assertDatabaseHas('invoices', ['id' => $id, 'grand_total' => 308]);
        $data['due_date'] = '2026-11-01';
        $this->put(route('admin.invoices.update', $id), $data)->assertSessionHasErrors('due_date');
        $this->post(route('admin.invoices.store'), array_replace($data, ['items' => []]))->assertSessionHasErrors('items');
    }

    public function test_projects_expenses_and_reports_use_live_records_and_currency_groups(): void
    {
        $this->actingAs($this->administrator());
        $customer = $this->customer();
        $this->post(route('admin.office.store', 'projects'), ['title' => 'Project', 'reference' => 'PRJ-001', 'customer_id' => $customer, 'status' => 'planned', 'priority' => 'normal', 'currency' => 'INR'])->assertSessionHasNoErrors();
        $project = DB::table('projects')->value('id');
        $this->get(route('admin.office.edit', ['projects', $project]))->assertOk();
        $this->post(route('admin.office.store', 'expense-categories'), ['name' => 'Hosting', 'is_active' => 1])->assertSessionHasNoErrors();
        $category = DB::table('expense_categories')->value('id');
        $this->post(route('admin.office.store', 'expenses'), ['title' => 'Server fees', 'expense_category_id' => $category, 'amount' => 150, 'currency' => 'INR', 'expense_date' => '2026-11-14'])->assertSessionHasNoErrors();
        $expense = DB::table('expenses')->value('id');
        $this->get(route('admin.office.edit', ['expenses', $expense]))->assertOk();
        $this->get(route('admin.reports.show', ['expenses', 'from' => '2026-11-01', 'to' => '2026-11-30']))->assertOk()->assertSee('Server fees')->assertSee('150.00');
        $this->get(route('admin.reports.show', ['expenses', 'from' => '2026-12-01']))->assertOk()->assertDontSee('Server fees');
        $id = $this->invoice($customer);
        $this->get(route('admin.reports.show', 'sales'))->assertOk()->assertDontSee('INV-000001');
        $this->patch(route('admin.invoices.status', $id), ['status' => 'issued'])->assertSessionHasNoErrors();
        $this->get(route('admin.reports.show', 'sales'))->assertOk()->assertSee('INV-000001')->assertSee('198.00');
    }

    public function test_user_management_hashes_passwords_preserves_passwords_and_prevents_self_demotion(): void
    {
        $admin = $this->administrator();
        $this->actingAs($admin);
        $data = ['name' => 'Staff Member', 'email' => 'staff@example.com', 'password' => 'a-strong-password', 'role' => 'staff'];
        $this->post(route('admin.office.store', 'users'), $data)->assertSessionHasNoErrors();
        $staff = User::where('email', $data['email'])->firstOrFail();
        $this->assertTrue(Hash::check($data['password'], $staff->password));
        $this->put(route('admin.office.update', ['users', $staff->id]), array_replace($data, ['password' => '']))->assertSessionHasNoErrors();
        $this->assertSame($staff->password, $staff->fresh()->password);
        $this->put(route('admin.office.update', ['users', $admin->id]), ['name' => $admin->name, 'email' => $admin->email, 'role' => 'staff'])->assertSessionHasErrors('role');
    }

    public function test_guests_and_staff_cannot_access_business_or_financial_actions(): void
    {
        $customer = $this->customer();
        $this->get(route('admin.office.index', 'customers'))->assertRedirect(route('login'));
        $this->post(route('admin.invoices.store'), $this->invoiceData($customer))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.office.index', 'customers'))->assertForbidden();
        $this->get(route('admin.invoices.index'))->assertForbidden();
        $this->post(route('admin.wallet.store', $customer), [])->assertForbidden();
        $this->post(route('admin.invoices.store'), $this->invoiceData($customer))->assertForbidden();
        $this->assertDatabaseCount('invoices', 0);
        $this->assertDatabaseCount('wallet_entries', 0);
    }
}

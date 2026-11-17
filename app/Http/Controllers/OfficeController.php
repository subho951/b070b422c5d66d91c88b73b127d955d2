<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OfficeController extends Controller
{
    /** @return array{label: string, fields: array<string, string>, table?: string} */
    private function definition(string $module): array
    {
        abort_unless(config('office.'.$module), 404);

        return config('office.'.$module);
    }

    private function records(string $module): Builder
    {
        $definition = $this->definition($module);
        $query = DB::table($definition['table'] ?? 'office_records');
        if (! isset($definition['table'])) {
            $query->where('module', $module);
        }
        if (! in_array($module, ['users', 'expense-categories'], true)) {
            $query->whereNull('deleted_at');
        }

        return $query;
    }

    public function index(Request $request, string $module): View
    {
        $definition = $this->definition($module);
        $records = $this->records($module);
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $records->where(in_array($module, ['expenses', 'projects'], true) ? 'title' : 'name', 'like', '%'.$search.'%');
        }

        return view('admin.office-index', ['module' => $module, 'definition' => $definition, 'records' => $records->orderByDesc('id')->paginate(20)->withQueryString()]);
    }

    public function form(string $module, ?int $id = null): View
    {
        $definition = $this->definition($module);
        $record = $id ? $this->records($module)->find($id) : null;
        abort_if($id && ! $record, 404);
        if ($record && ! isset($definition['table'])) {
            $record = (object) (json_decode($record->data, true) + ['id' => $record->id]);
        }

        return view('admin.office-form', compact('module', 'definition', 'record') + $this->choices());
    }

    /** @return array<string, mixed> */
    private function choices(): array
    {
        return [
            'currencies' => DB::table('currencies')->where('is_active', true)->get(),
            'customers' => DB::table('customers')->whereNull('deleted_at')->orderBy('name')->get(),
            'categories' => DB::table('expense_categories')->orderBy('name')->get(),
        ];
    }

    public function save(Request $request, string $module, ?int $id = null): RedirectResponse
    {
        $definition = $this->definition($module);
        abort_if($id && ! $this->records($module)->find($id), 404);
        $rules = $definition['fields'];
        foreach (['customers' => 'email', 'users' => 'email', 'projects' => 'reference', 'expense-categories' => 'name'] as $uniqueModule => $field) {
            if ($module === $uniqueModule) {
                $rules[$field] = [...explode('|', $rules[$field]), Rule::unique($definition['table'], $field)->ignore($id)];
            }
        }
        if ($module === 'users' && ! $id) {
            $rules['password'] = 'required|string|min:12|max:255';
        }
        $data = $request->validate($rules);
        if ($module === 'users') {
            if ($id === $request->user()->id && $data['role'] !== 'administrator') {
                throw ValidationException::withMessages(['role' => 'You cannot remove your own administrator access.']);
            }
            if (! empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                unset($data['password']);
            }
        }
        if (! isset($definition['table'])) {
            $data = ['module' => $module, 'name' => $data['name'], 'data' => json_encode($data, JSON_THROW_ON_ERROR)];
        }
        $data['updated_at'] = now();
        if ($id) {
            $this->records($module)->where('id', $id)->update($data);
        } else {
            $data['created_at'] = now();
            DB::table($definition['table'] ?? 'office_records')->insert($data);
        }

        return to_route('admin.office.index', $module)->with('success', $definition['label'].' saved.');
    }

    public function destroy(Request $request, string $module, int $id): RedirectResponse
    {
        $definition = $this->definition($module);
        abort_unless($this->records($module)->find($id), 404);
        if (in_array($module, ['users', 'expense-categories'], true)) {
            throw ValidationException::withMessages(['record' => 'Update the role or active status instead of deleting this record.']);
        }
        if ($module === 'customers' && (DB::table('invoices')->where('customer_id', $id)->exists() || DB::table('wallet_entries')->where('customer_id', $id)->exists() || DB::table('projects')->where('customer_id', $id)->exists())) {
            throw ValidationException::withMessages(['record' => 'This customer has financial or project history. Set their status to inactive instead.']);
        }
        $this->records($module)->where('id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);

        return back()->with('success', $definition['label'].' record archived.');
    }

    public function wallet(int $customer): View
    {
        $record = $this->records('customers')->find($customer);
        abort_unless($record, 404);
        $balances = DB::table('wallet_entries')->where('customer_id', $customer)->selectRaw("currency, SUM(CASE WHEN direction = 'credit' THEN amount ELSE -amount END) AS balance")->groupBy('currency')->get();

        return view('admin.wallet', ['customer' => $record, 'balances' => $balances, 'legacyTransactions' => DB::table('legacy_customer_transactions')->where('customer_id', $customer)->orderByDesc('id')->paginate(20, ['*'], 'legacy_page'), 'entries' => DB::table('wallet_entries')->where('customer_id', $customer)->orderByDesc('id')->paginate(20)] + $this->choices());
    }

    private function walletBalance(int $customer, string $currency): int
    {
        return (int) round((float) DB::table('wallet_entries')->where('customer_id', $customer)->where('currency', $currency)->selectRaw("COALESCE(SUM(CASE WHEN direction = 'credit' THEN amount ELSE -amount END), 0) AS balance")->value('balance') * 1000);
    }

    public function walletStore(Request $request, int $customer): RedirectResponse
    {
        $data = $request->validate(['currency' => ['required', 'exists:currencies,code'], 'direction' => ['required', Rule::in(['credit', 'debit'])], 'amount' => ['required', 'numeric', 'gt:0', 'max:999999999'], 'transaction_date' => ['required', 'date'], 'notes' => ['required', 'string', 'max:5000']]);
        DB::transaction(function () use ($request, $customer, $data): void {
            abort_unless($this->records('customers')->where('id', $customer)->lockForUpdate()->first(), 404);
            $precision = DB::table('currencies')->where('code', $data['currency'])->value('decimal_places');
            $amount = round((float) $data['amount'], $precision);
            if ($amount <= 0 || ($data['direction'] === 'debit' && $this->walletBalance($customer, $data['currency']) < (int) round($amount * 1000))) {
                throw ValidationException::withMessages(['amount' => 'Enter a positive amount within the available wallet balance.']);
            }
            DB::table('wallet_entries')->insert(array_merge($data, ['amount' => $amount, 'customer_id' => $customer, 'reference' => 'WAL-'.Str::uuid(), 'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]));
        });

        return back()->with('success', 'Wallet transaction recorded.');
    }

    public function invoices(Request $request): View
    {
        $query = DB::table('invoices');
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->integer('customer_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        return view('admin.invoices', ['invoices' => $query->orderByDesc('id')->paginate(20)->withQueryString()] + $this->choices());
    }

    public function invoiceForm(?int $id = null): View
    {
        $invoice = $id ? DB::table('invoices')->find($id) : null;
        abort_if($id && ! $invoice, 404);
        abort_if($invoice && $invoice->status !== 'draft', 403, 'Only draft invoices can be edited.');

        return view('admin.invoice-form', ['invoice' => $invoice, 'companies' => $this->records('companies')->orderBy('name')->get(), 'banks' => $this->records('banks')->orderBy('name')->get(), 'items' => $id ? DB::table('invoice_items')->where('invoice_id', $id)->orderBy('sort_order')->get()->map(fn (object $item): array => (array) $item)->all() : []] + $this->choices());
    }

    public function invoiceSave(Request $request, ?int $id = null): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id,deleted_at,NULL'], 'currency' => ['required', 'exists:currencies,code'],
            'issue_date' => ['required', 'date'], 'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'seller_name' => ['required', 'string', 'max:180'], 'seller_address' => ['required', 'string', 'max:5000'], 'seller_email' => ['nullable', 'email', 'max:255'],
            'seller_tax_identifier' => ['nullable', 'string', 'max:100'], 'notes' => ['nullable', 'string', 'max:5000'], 'payment_terms' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1', 'max:100'], 'items.*.description' => ['required', 'string', 'max:5000'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:100000'], 'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'items.*.discount_amount' => ['required', 'numeric', 'min:0', 'max:999999999'], 'items.*.tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);
        $invoiceId = DB::transaction(function () use ($request, $id, $data): int {
            $existing = $id ? DB::table('invoices')->where('id', $id)->lockForUpdate()->first() : null;
            abort_if($id && ! $existing, 404);
            abort_if($existing && $existing->status !== 'draft', 403);
            $customer = $this->records('customers')->find($data['customer_id']);
            abort_unless($customer, 404);
            $precision = (int) DB::table('currencies')->where('code', $data['currency'])->value('decimal_places');
            $factor = 10 ** $precision;
            $items = [];
            $subtotal = $discount = $tax = $total = 0;
            foreach (array_values($data['items']) as $index => $item) {
                $lineSubtotal = (int) round(round((float) $item['quantity'], 4) * round((float) $item['unit_price'], 6) * $factor);
                $lineDiscount = (int) round((float) $item['discount_amount'] * $factor);
                if ($lineDiscount > $lineSubtotal) {
                    throw ValidationException::withMessages(['items.'.$index.'.discount_amount' => 'Discount cannot exceed the line subtotal.']);
                }
                $item['tax_rate'] = round((float) $item['tax_rate'], 4);
                $lineTax = (int) round(($lineSubtotal - $lineDiscount) * $item['tax_rate'] / 100);
                $lineTotal = $lineSubtotal - $lineDiscount + $lineTax;
                $items[] = array_merge($item, ['quantity' => round((float) $item['quantity'], 4), 'unit_price' => round((float) $item['unit_price'], 6), 'discount_amount' => $lineDiscount / $factor, 'subtotal' => $lineSubtotal / $factor, 'tax_amount' => $lineTax / $factor, 'total' => $lineTotal / $factor, 'sort_order' => $index, 'created_at' => now(), 'updated_at' => now()]);
                $subtotal += $lineSubtotal;
                $discount += $lineDiscount;
                $tax += $lineTax;
                $total += $lineTotal;
            }
            if ($total > 999999999 * $factor) {
                throw ValidationException::withMessages(['items' => 'Invoice total cannot exceed 999,999,999 in its currency.']);
            }
            unset($data['items']);
            $data = array_merge($data, ['currency_decimal_places' => $precision, 'customer_name' => $customer->name, 'customer_email' => $customer->email, 'billing_address' => $customer->billing_address ?? '', 'billing_country' => $customer->country, 'customer_tax_identifier' => $customer->tax_identifier, 'subtotal' => $subtotal / $factor, 'discount_total' => $discount / $factor, 'tax_total' => $tax / $factor, 'grand_total' => $total / $factor, 'updated_at' => now()]);
            if ($id) {
                DB::table('invoices')->where('id', $id)->update($data);
                DB::table('invoice_items')->where('invoice_id', $id)->delete();
            } else {
                $sequence = DB::table('document_sequences')->where('key', 'invoice')->lockForUpdate()->first();
                $number = $sequence->last_number + 1;
                DB::table('document_sequences')->where('key', 'invoice')->update(['last_number' => $number, 'updated_at' => now()]);
                $id = DB::table('invoices')->insertGetId($data + ['invoice_number' => $sequence->prefix.str_pad((string) $number, 6, '0', STR_PAD_LEFT), 'created_by' => $request->user()->id, 'created_at' => now()]);
            }
            foreach ($items as $item) {
                DB::table('invoice_items')->insert($item + ['invoice_id' => $id]);
            }

            return $id;
        });

        return to_route('admin.invoices.show', $invoiceId)->with('success', 'Invoice draft saved.');
    }

    private function paid(int $invoice): int
    {
        return (int) round((float) DB::table('payments')->where('invoice_id', $invoice)->where('status', 'recorded')->sum('amount') * 1000);
    }

    public function invoiceShow(int $id): View
    {
        $invoice = DB::table('invoices')->find($id);
        abort_unless($invoice, 404);

        return view('admin.invoice-show', ['invoice' => $invoice, 'items' => DB::table('invoice_items')->where('invoice_id', $id)->orderBy('sort_order')->get(), 'payments' => DB::table('payments')->where('invoice_id', $id)->orderByDesc('id')->get(), 'paid' => $this->paid($id) / 1000, 'walletBalance' => $this->walletBalance($invoice->customer_id, $invoice->currency) / 1000]);
    }

    public function invoiceStatus(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['issued', 'cancelled'])], 'reason' => ['required_if:status,cancelled', 'nullable', 'string', 'max:5000']]);
        DB::transaction(function () use ($request, $id, $data): void {
            $invoice = DB::table('invoices')->where('id', $id)->lockForUpdate()->first();
            abort_unless($invoice, 404);
            if (($data['status'] === 'issued' && ($invoice->status !== 'draft' || $invoice->grand_total <= 0)) || ($data['status'] === 'cancelled' && ($invoice->status === 'cancelled' || $this->paid($id) > 0))) {
                throw ValidationException::withMessages(['status' => 'Issue a positive draft, or void recorded payments before cancellation.']);
            }
            $update = ['status' => $data['status'], 'updated_at' => now()];
            if ($data['status'] === 'issued') {
                $update['finalized_at'] = now();
            } else {
                $update['cancelled_at'] = now();
                $update['cancellation_reason'] = $data['reason'];
            }
            DB::table('invoices')->where('id', $id)->update($update);
            DB::table('invoice_status_history')->insert(['invoice_id' => $id, 'from_status' => $invoice->status, 'to_status' => $data['status'], 'reason' => $data['reason'] ?? null, 'changed_by' => $request->user()->id, 'created_at' => now()]);
        });

        return back()->with('success', 'Invoice status updated.');
    }

    public function paymentStore(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'gt:0', 'max:999999999'], 'method' => ['required', Rule::in(['bank_transfer', 'cash', 'card', 'upi', 'wallet'])], 'payment_date' => ['required', 'date'], 'external_reference' => ['nullable', 'string', 'max:180'], 'notes' => ['nullable', 'string', 'max:5000']]);
        DB::transaction(function () use ($request, $id, $data): void {
            $invoice = DB::table('invoices')->where('id', $id)->lockForUpdate()->first();
            abort_unless($invoice, 404);
            DB::table('customers')->where('id', $invoice->customer_id)->lockForUpdate()->first();
            $amount = (int) round(round((float) $data['amount'], $invoice->currency_decimal_places) * 1000);
            if (! in_array($invoice->status, ['issued', 'partially_paid'], true) || $amount <= 0 || $amount > (int) round($invoice->grand_total * 1000) - $this->paid($id)) {
                throw ValidationException::withMessages(['amount' => 'Payment must be positive and within the balance of an issued invoice.']);
            }
            if ($data['method'] === 'wallet' && $amount > $this->walletBalance($invoice->customer_id, $invoice->currency)) {
                throw ValidationException::withMessages(['amount' => 'Insufficient wallet funds in this invoice currency.']);
            }
            $reference = 'PAY-'.Str::uuid();
            $paymentId = DB::table('payments')->insertGetId(array_merge($data, ['amount' => $amount / 1000, 'reference' => $reference, 'invoice_id' => $id, 'currency' => $invoice->currency, 'recorded_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]));
            if ($data['method'] === 'wallet') {
                DB::table('wallet_entries')->insert(['customer_id' => $invoice->customer_id, 'invoice_id' => $id, 'currency' => $invoice->currency, 'reference' => $reference, 'direction' => 'debit', 'amount' => $amount / 1000, 'transaction_date' => $data['payment_date'], 'notes' => 'Payment for '.$invoice->invoice_number, 'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
            }
            DB::table('payment_status_history')->insert(['payment_id' => $paymentId, 'to_status' => 'recorded', 'changed_by' => $request->user()->id, 'created_at' => now()]);
            $this->refreshInvoiceStatus($id, $request->user()->id);
        });

        return back()->with('success', 'Payment recorded.');
    }

    private function refreshInvoiceStatus(int $id, int $actor): void
    {
        $invoice = DB::table('invoices')->find($id);
        $paid = $this->paid($id);
        $status = $paid >= (int) round($invoice->grand_total * 1000) ? 'paid' : ($paid > 0 ? 'partially_paid' : 'issued');
        DB::table('invoices')->where('id', $id)->update(['status' => $status, 'updated_at' => now()]);
        DB::table('invoice_status_history')->insert(['invoice_id' => $id, 'from_status' => $invoice->status, 'to_status' => $status, 'changed_by' => $actor, 'created_at' => now()]);
    }

    public function paymentVoid(Request $request, int $id, int $payment): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:5000']]);
        DB::transaction(function () use ($request, $id, $payment, $data): void {
            $invoice = DB::table('invoices')->where('id', $id)->lockForUpdate()->first();
            abort_unless($invoice, 404);
            DB::table('customers')->where('id', $invoice->customer_id)->lockForUpdate()->first();
            $record = DB::table('payments')->where('invoice_id', $id)->where('id', $payment)->lockForUpdate()->first();
            abort_unless($record, 404);
            if ($record->status !== 'recorded') {
                throw ValidationException::withMessages(['payment' => 'This payment has already been voided.']);
            }
            DB::table('payments')->where('id', $payment)->update(['status' => 'voided', 'voided_at' => now(), 'void_reason' => $data['reason'], 'voided_by' => $request->user()->id, 'updated_at' => now()]);
            if ($record->method === 'wallet') {
                DB::table('wallet_entries')->insert(['customer_id' => $invoice->customer_id, 'invoice_id' => $id, 'currency' => $invoice->currency, 'reference' => 'VOID-'.$record->reference, 'direction' => 'credit', 'amount' => $record->amount, 'transaction_date' => today(), 'notes' => $data['reason'], 'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
            }
            DB::table('payment_status_history')->insert(['payment_id' => $payment, 'from_status' => 'recorded', 'to_status' => 'voided', 'reason' => $data['reason'], 'changed_by' => $request->user()->id, 'created_at' => now()]);
            $this->refreshInvoiceStatus($id, $request->user()->id);
        });

        return back()->with('success', 'Payment voided; wallet funds restored where applicable.');
    }

    public function loginLogs(): View
    {
        return view('admin.login-logs', ['records' => DB::table('activity_logs')->where('event', 'login')->orderByDesc('id')->paginate(30)]);
    }

    public function report(Request $request, string $type): View
    {
        abort_unless(in_array($type, ['sales', 'expenses'], true), 404);
        $data = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from'], 'currency' => ['nullable', 'exists:currencies,code']]);
        $query = DB::table($type === 'sales' ? 'invoices' : 'expenses');
        $date = $type === 'sales' ? 'issue_date' : 'expense_date';
        $amount = $type === 'sales' ? 'grand_total' : 'amount';
        if ($type === 'sales') {
            $query->whereNotIn('status', ['draft', 'cancelled']);
        } else {
            $query->whereNull('deleted_at');
        }
        foreach (['from' => '>=', 'to' => '<='] as $key => $operator) {
            if (! empty($data[$key])) {
                $query->whereDate($date, $operator, $data[$key]);
            }
        }
        if (! empty($data['currency'])) {
            $query->where('currency', $data['currency']);
        }
        $totals = (clone $query)->selectRaw('currency, SUM('.$amount.') AS total, COUNT(*) AS count')->groupBy('currency')->get();

        return view('admin.office-report', ['type' => $type, 'totals' => $totals, 'records' => $query->orderByDesc($date)->paginate(30)->withQueryString()] + $this->choices());
    }
}

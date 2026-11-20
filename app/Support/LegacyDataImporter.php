<?php

namespace App\Support;

use Illuminate\Database\Connection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class LegacyDataImporter
{
    /** @var array<string, list<array<string, mixed>>> */
    private array $rows = [];

    /** @var list<string> */
    private array $warnings = [];

    /** @var list<string> */
    private array $createdFiles = [];

    private string $sourceDatabase;

    private string $sourcePath;

    private int $created = 0;

    private int $skipped = 0;

    /** @return array<string, mixed> */
    public function run(Connection $source, string $sourcePath, bool $dryRun = true): array
    {
        $this->rows = $this->warnings = $this->createdFiles = [];
        $this->created = $this->skipped = 0;
        $this->sourceDatabase = basename($source->getDatabaseName());
        $this->sourcePath = $sourcePath;
        $counts = [];
        foreach ($source->getSchemaBuilder()->getTables($source->getSchemaBuilder()->getCurrentSchemaListing()) as $table) {
            $name = $table['name'];
            $counts[$name] = $source->table($name)->count();
            if (in_array($name, ['ci_sessions', 'sessions', 'migrations'], true)) {
                continue;
            }
            $indexes = $source->getSchemaBuilder()->getIndexes($name);
            $primary = collect($indexes)->firstWhere('primary', true)['columns'] ?? [];
            $query = $source->table($name);
            foreach ($primary as $column) {
                $query->orderBy($column);
            }
            $this->rows[$name] = $query->get()->map(function (object $row, int $index) use ($primary): array {
                $data = (array) $row;
                $data['__source_key'] = $primary ? implode(':', array_map(fn (string $key): string => (string) $data[$key], $primary)) : (string) $index;

                return $data;
            })->all();
        }
        if (! isset($this->rows['td_customer'], $this->rows['td_bill'], $this->rows['td_customer_payment'])) {
            throw new RuntimeException('The source is missing required customer, invoice or ledger tables.');
        }
        $summary = ['source_database' => $this->sourceDatabase, 'destination_database' => DB::connection()->getDatabaseName(), 'source_counts' => $counts, 'receipt_policy' => 'Unlinked receipts become wallet credits. Invoice charges remain historical debt entries.', 'excluded_tables' => ['ci_sessions', 'sessions', 'migrations'], 'dry_run' => $dryRun];
        if ($dryRun) {
            return $summary;
        }
        $backup = $this->backup();
        try {
            DB::transaction(function (): void {
                foreach ($this->rows as $table => $rows) {
                    foreach ($rows as $row) {
                        $key = $row['__source_key'];
                        unset($row['__source_key']);
                        $this->archive($table, $key, $row);
                    }
                }
                $this->importCustomers();
                $this->importMasters();
                $this->importContent();
                $this->importUsers();
                $this->importInvoices();
                $this->importLedger();
                $this->importOtherData();
                $sequence = DB::table('document_sequences')->where('key', 'invoice')->lockForUpdate()->first();
                if ($sequence) {
                    $largest = DB::table('invoices')->where('invoice_number', 'like', $sequence->prefix.'%')->pluck('invoice_number')->map(fn (string $number): int => ctype_digit(substr($number, strlen($sequence->prefix))) ? (int) substr($number, strlen($sequence->prefix)) : 0)->max() ?? 0;
                    DB::table('document_sequences')->where('key', 'invoice')->update(['last_number' => max($sequence->last_number, $largest), 'updated_at' => now()]);
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($this->createdFiles);
            throw $exception;
        }
        $summary += ['backup' => $backup, 'created_target_records' => $this->created, 'existing_mappings_skipped' => $this->skipped, 'warnings' => array_values(array_unique($this->warnings))];
        $report = 'legacy-imports/report-'.now()->format('Ymd-His').'-'.Str::uuid().'.json';
        if (! Storage::disk('local')->put($report, json_encode($summary, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR))) {
            throw new RuntimeException('The import succeeded but its report could not be saved.');
        }

        return $summary + ['report' => $report];
    }

    private function backup(): string
    {
        $tables = [];
        foreach (DB::connection()->getSchemaBuilder()->getTables(DB::connection()->getSchemaBuilder()->getCurrentSchemaListing()) as $table) {
            $tables[$table['name']] = DB::table($table['name'])->get()->all();
        }
        $path = 'legacy-imports/before-import-'.now()->format('Ymd-His').'-'.Str::uuid().'.json.enc';
        if (! Storage::disk('local')->put($path, Crypt::encryptString(json_encode($tables, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE)))) {
            throw new RuntimeException('Could not create the encrypted destination backup. No data was imported.');
        }

        return $path;
    }

    /** @param array<string, mixed> $payload */
    private function archive(string $table, string $key, array $payload): int
    {
        $query = DB::table('legacy_import_records')->where('source_database', $this->sourceDatabase)->where('source_table', $table)->where('source_key', $key);
        $existing = $query->value('id');
        if ($existing) {
            return (int) $existing;
        }

        return DB::table('legacy_import_records')->insertGetId(['source_database' => $this->sourceDatabase, 'source_table' => $table, 'source_key' => $key, 'encrypted_payload' => Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE)), 'created_at' => now(), 'updated_at' => now()]);
    }

    private function mapping(string $table, string|int $key): ?object
    {
        return DB::table('legacy_import_records')->where('source_database', $this->sourceDatabase)->where('source_table', $table)->where('source_key', (string) $key)->first();
    }

    private function targetId(string $table, string|int $key): ?int
    {
        $mapping = $this->mapping($table, $key);

        return $mapping?->target_id ? (int) $mapping->target_id : null;
    }

    /** @param array<string, mixed> $data */
    private function insert(string $sourceTable, string|int $key, string $targetTable, array $data): int
    {
        $mapping = $this->mapping($sourceTable, $key);
        if (! $mapping) {
            $this->archive($sourceTable, (string) $key, []);
            $mapping = $this->mapping($sourceTable, $key);
        }
        if ($mapping->target_id) {
            if (! DB::table($mapping->target_table)->where('id', $mapping->target_id)->exists()) {
                throw new RuntimeException('Previously imported target is missing: '.$sourceTable.' #'.$key);
            }
            $this->skipped++;

            return (int) $mapping->target_id;
        }
        $timestamps = ['created_at' => now()];
        if ($targetTable !== 'activity_logs') {
            $timestamps['updated_at'] = now();
        }
        $id = DB::table($targetTable)->insertGetId($data + $timestamps);
        DB::table('legacy_import_records')->where('id', $mapping->id)->update(['target_table' => $targetTable, 'target_id' => $id, 'updated_at' => now()]);
        $this->created++;

        return $id;
    }

    private function date(?string $value, ?string $fallback = null): string
    {
        try {
            if ($value && preg_match('/[1-9]/', $value)) {
                return Carbon::parse($value)->format('Y-m-d');
            }
        } catch (\Throwable) {
        }

        return $fallback ?? now()->toDateString();
    }

    private function email(string $email, string $type, int $id): string
    {
        $email = trim($email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && ! DB::table($type === 'customer' ? 'customers' : 'users')->where('email', $email)->exists()) {
            return $email;
        }
        $this->warnings[] = ucfirst($type).' #'.$id.': missing, invalid or duplicate email; assigned a non-deliverable import address. Original value retained in encrypted archive.';

        return 'legacy-'.$type.'-'.$id.'-'.substr(sha1($this->sourceDatabase), 0, 8).'@import.invalid';
    }

    private function customer(int $sourceId): int
    {
        $id = $this->targetId('td_customer', $sourceId) ?? $this->targetId('__missing_customer__', $sourceId);
        if ($id) {
            return $id;
        }
        $this->warnings[] = 'Invoice refers to missing legacy customer #'.$sourceId.'. An inactive placeholder preserves its history.';

        return $this->insert('__missing_customer__', $sourceId, 'customers', ['name' => 'Legacy customer #'.$sourceId.' (source record missing)', 'email' => $this->email('', 'customer', $sourceId), 'country' => 'India', 'billing_currency' => 'INR', 'status' => 'inactive', 'internal_notes' => 'Customer record was absent from source database; retained for historical invoices.']);
    }

    private function importCustomers(): void
    {
        foreach ($this->rows['td_customer'] as $row) {
            $id = (int) $row['customer_id'];
            if ($this->targetId('td_customer', $id)) {
                $this->skipped++;

                continue;
            }
            $this->insert('td_customer', $id, 'customers', [
                'name' => mb_substr($row['customer_name'] ?: 'Legacy customer #'.$id, 0, 180), 'type' => 'company',
                'email' => $this->email($row['customer_email'] ?? '', 'customer', $id), 'phone' => mb_substr($row['customer_phone'] ?? '', 0, 40), 'country' => 'India',
                'billing_address' => $row['billing_address'] ?? '', 'tax_identifier' => mb_substr($row['billing_gst'] ?? '', 0, 100), 'billing_currency' => 'INR',
                'domain_provider' => $row['domain_provider'] ?? null, 'server_provider' => $row['server_provider'] ?? null,
                'renewal_date' => ! empty($row['renewal']) && $row['renewal'] !== '0000-00-00' ? $this->date($row['renewal']) : null, 'renewal_amount' => $row['renewal_amount'] ?? null,
                'status' => ($row['published'] ?? 1) ? 'active' : 'inactive', 'created_at' => $this->date($row['created'] ?? null),
                'internal_notes' => 'Imported legacy customer #'.$id.'. Original email: '.($row['customer_email'] ?? '').'. Company reference: '.($row['site_id'] ?? '').'. Legacy balance due: '.($row['opening_balance'] ?? 0).' INR. Domain/server credentials retained only in encrypted legacy archive.',
            ]);
        }
    }

    /** @param array<string, mixed> $data */
    private function master(string $table, int $key, string $module, array $data): int
    {
        return $this->insert($table, $key, 'office_records', ['module' => $module, 'name' => mb_substr($data['name'], 0, 180), 'data' => json_encode($data, JSON_THROW_ON_ERROR)]);
    }

    private function importMasters(): void
    {
        foreach ($this->rows['td_site_settings'] ?? [] as $row) {
            $this->master('td_site_settings', $row['site_id'], 'companies', ['name' => $row['site_name'], 'email' => $row['site_email_address'], 'phone' => $row['site_phone'], 'address' => $row['site_address'], 'tax_identifier' => $row['site_gstn'], 'pan' => $row['site_pan'], 'state_code' => $row['site_state_code'], 'featured_image_id' => $this->media($row['site_logo'], '')]);
        }
        foreach ($this->rows['td_bank'] ?? [] as $row) {
            $company = collect($this->rows['td_site_settings'] ?? [])->firstWhere('site_id', $row['site_id']);
            $this->master('td_bank', $row['bank_id'], 'banks', ['name' => $row['bank_name'], 'account_holder' => $company['site_name'] ?? 'Legacy company', 'account_number' => $row['acc_no'], 'branch' => $row['bank_branch'], 'ifsc_swift' => $row['bank_ifsc'], 'notes' => 'Legacy company #'.$row['site_id']]);
        }
        foreach (['td_worlflow' => ['workflow', 'workflow_id', 'title'], 'td_clients' => ['clients', 'client_id', 'client_name'], 'td_team' => ['team', 'team_id', 'name'], 'td_package' => ['packages', 'package_id', 'title'], 'db_image_category' => ['project-categories', 'id', 'category_name'], 'td_product' => ['products', 'product_id', 'product_name'], 'gst_state' => ['gst-states', 'state_id', 'state']] as $table => [$module,$key,$name]) {
            foreach ($this->rows[$table] ?? [] as $row) {
                $data = ['name' => $row[$name], 'description' => $row['description'] ?? $row['product_description'] ?? '', 'designation' => $row['designation'] ?? '', 'amount' => $row['price'] ?? 0, 'currency' => 'INR', 'sort_order' => (int) $row[$key], 'legacy_reference' => $row[$key], 'legacy_published' => $row['published'] ?? null];
                if (! empty($row['client_logo'])) {
                    $data['featured_image_id'] = $this->media($row['client_logo'], $table === 'td_team' ? 'team' : 'client');
                }
                if ($table === 'td_package') {
                    $data['description'] = trim($data['description']."\n".($row['feature'] ?? ''));
                }
                $this->master($table, $row[$key], $module, $data);
            }
        }
    }

    private function media(?string $filename, string $folder): ?int
    {
        if (! $filename || basename($filename) !== $filename) {
            return null;
        }
        $key = $folder.'/'.$filename;
        if ($id = $this->targetId('__media__', $key)) {
            return $id;
        }
        $base = realpath($this->sourcePath.'/uploads');
        $file = realpath($this->sourcePath.'/uploads/'.($folder ? $folder.'/' : '').$filename);
        if (! $base || ! $file || ! str_starts_with(str_replace('\\', '/', $file), str_replace('\\', '/', $base).'/') || ! is_file($file)) {
            $this->warnings[] = 'Missing legacy image: '.$key;

            return null;
        }
        $image = @getimagesize($file);
        if (! $image || ! in_array($image['mime'], ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
            $this->warnings[] = 'Unsupported legacy image: '.$key;

            return null;
        }
        $path = 'images/legacy/'.sha1($this->sourceDatabase.'/'.$key).'.'.strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (! Storage::disk('public')->exists($path)) {
            if (! Storage::disk('public')->put($path, file_get_contents($file))) {
                throw new RuntimeException('Could not copy legacy image: '.$key);
            }
            $this->createdFiles[] = $path;
        }

        return $this->insert('__media__', $key, 'media_assets', ['disk' => 'public', 'path' => $path, 'original_name' => $filename, 'title' => $filename, 'mime_type' => $image['mime'], 'size_bytes' => filesize($file), 'width' => $image[0], 'height' => $image[1]]);
    }

    private function importContent(): void
    {
        foreach ($this->rows['td_services'] ?? [] as $row) {
            $this->insert('td_services', $row['service_id'], 'services', ['title' => $row['title'], 'slug' => 'legacy-service-'.$row['service_id'].'-'.Str::slug($row['title']), 'description' => strip_tags($row['description']), 'content' => strip_tags($row['description']), 'status' => 'draft', 'sort_order' => $row['service_id'], 'created_at' => $this->date($row['created'] ?? null)]);
        }
        foreach ($this->rows['td_static_data'] ?? [] as $row) {
            $this->insert('td_static_data', $row['static_id'], 'pages', ['title' => $row['title'], 'slug' => 'legacy-page-'.$row['static_id'], 'description' => strip_tags($row['description']), 'content' => strip_tags($row['description']), 'status' => 'draft']);
        }
        foreach ($this->rows['td_testimonials'] ?? [] as $row) {
            $this->insert('td_testimonials', $row['testimonial_id'], 'testimonials', ['title' => 'Feedback from '.$row['name'], 'name' => $row['name'], 'company' => $row['company_name'], 'description' => $row['description'], 'status' => 'draft', 'consent_confirmed' => false, 'created_at' => $this->date($row['created'] ?? null)]);
        }
        foreach ($this->rows['db_image_galleries'] ?? [] as $row) {
            $category = collect($this->rows['db_image_category'] ?? [])->firstWhere('id', $row['category_id']);
            $legacySlug = 'legacy-project-'.$row['id'].'-'.Str::slug($row['image_title']);
            $knownProject = config('portfolio-catalog.legacy')[$legacySlug] ?? null;
            $this->insert('db_image_galleries', $row['id'], 'portfolio_projects', ['title' => $knownProject[1] ?? $row['image_title'], 'slug' => $knownProject[0] ?? $legacySlug, 'category' => mb_substr($category['category_name'] ?? '', 0, 100), 'description' => strip_tags($row['product_description'] ?? ''), 'content' => strip_tags($row['product_description'] ?? ''), 'seo_description' => strip_tags($row['meta_description'] ?? ''), 'featured_image_id' => $this->media($row['images'], 'project'), 'status' => 'draft', 'is_demonstration' => false, 'created_at' => $this->date($row['created'] ?? null)]);
        }
    }

    private function importUsers(): void
    {
        foreach ($this->rows['td_users'] ?? [] as $row) {
            if ($this->targetId('td_users', $row['id'])) {
                $this->skipped++;

                continue;
            }
            $password = $row['password_original'] ?? '';
            $canRecover = $password !== '' && hash_equals(strtolower($row['password']), md5($password));
            if (! $canRecover) {
                $this->warnings[] = 'User #'.$row['id'].': legacy password could not be verified; a password reset is required.';
            }
            $this->insert('td_users', $row['id'], 'users', ['name' => $row['name'] ?: $row['username'], 'email' => $this->email($row['email'] ?? '', 'user', $row['id']), 'password' => Hash::make($canRecover ? $password : Str::random(64)), 'role' => ($row['published'] ?? 0) && $row['user_type'] === 'MA' ? 'administrator' : 'staff', 'created_at' => now()]);
        }
    }

    private function importInvoices(): void
    {
        foreach ($this->rows['td_bill'] as $row) {
            if ($this->targetId('td_bill', $row['bill_id'])) {
                $this->skipped++;

                continue;
            }
            $customerId = $this->customer($row['customer_id']);
            $customer = DB::table('customers')->find($customerId);
            $company = collect($this->rows['td_site_settings'] ?? [])->firstWhere('site_id', $row['site_id']);
            $number = trim($row['bill_no']) ?: 'LEGACY-'.$row['bill_id'];
            if (DB::table('invoices')->where('invoice_number', $number)->exists()) {
                $this->warnings[] = 'Invoice #'.$row['bill_id'].': duplicate invoice number retained with legacy suffix.';
                $number = mb_substr($number, 0, 40).'-LEGACY-'.$row['bill_id'];
            }
            $date = $this->date($row['bill_date']);
            $id = $this->insert('td_bill', $row['bill_id'], 'invoices', [
                'invoice_number' => mb_substr($number, 0, 60), 'customer_id' => $customerId, 'issue_date' => $date, 'due_date' => $date, 'currency' => 'INR', 'currency_decimal_places' => 2,
                'customer_name' => $customer->name, 'customer_email' => $customer->email, 'billing_address' => $customer->billing_address ?? '', 'billing_country' => $customer->country, 'customer_tax_identifier' => $customer->tax_identifier,
                'seller_name' => mb_substr($company['site_name'] ?? 'Legacy company #'.$row['site_id'], 0, 180), 'seller_address' => $company['site_address'] ?? '', 'seller_email' => $company['site_email_address'] ?? null, 'seller_tax_identifier' => $company['site_gstn'] ?? null,
                'subtotal' => round($row['taxable_amt'], 2), 'discount_total' => round($row['discount_amt'], 2), 'tax_total' => round($row['total_gst'], 2), 'grand_total' => round($row['grand_total'], 2), 'tax_label' => 'GST', 'status' => 'issued', 'finalized_at' => $date,
                'notes' => 'Imported legacy invoice #'.$row['bill_id'].'. Original invoice number: '.$row['bill_no'].'. Source did not record a due date; issue date used. Shipping address: '.($row['shipping_address'] ?? '').'. Original unlinked receipts are held as customer wallet credit.',
                'created_at' => $date,
            ]);
            DB::table('invoice_status_history')->insert(['invoice_id' => $id, 'to_status' => 'issued', 'reason' => 'Legacy invoice imported; unlinked receipts require allocation.', 'created_at' => now()]);
        }
        foreach ($this->rows['td_bill_details'] ?? [] as $row) {
            $invoice = $this->targetId('td_bill', $row['bill_id']);
            if (! $invoice) {
                $this->warnings[] = 'Invoice item #'.$row['bill_detail_id'].' has no source invoice; preserved in encrypted archive.';

                continue;
            }
            $product = collect($this->rows['td_product'] ?? [])->firstWhere('product_id', $row['product_id']);
            $tax = round((float) $row['cgst_amt'] + (float) $row['sgst_amt'] + (float) $row['igst_amt'], 2);
            $base = round((float) $row['actual_amt'], 2);
            $this->insert('td_bill_details', $row['bill_detail_id'], 'invoice_items', ['invoice_id' => $invoice, 'description' => ($product['product_name'] ?? 'Legacy product #'.$row['product_id']).(! empty($product['hsn_code']) ? ' (HSN '.$product['hsn_code'].')' : ''), 'quantity' => $row['qty'], 'unit_price' => $row['rate'], 'subtotal' => $base, 'tax_label' => 'GST', 'tax_rate' => $base > 0 ? round($tax / $base * 100, 4) : 0, 'tax_amount' => $tax, 'total' => $row['subtotal'], 'sort_order' => $row['bill_detail_id']]);
        }
    }

    private function importLedger(): void
    {
        foreach ($this->rows['td_customer_payment'] as $row) {
            $customer = $this->customer($row['customer_id']);
            $invoiceRow = collect($this->rows['td_bill'])->first(fn (array $bill): bool => ($bill['bill_no'] ?? '') !== '' && $bill['bill_no'] === ($row['bill_no'] ?? '') && (int) $bill['customer_id'] === (int) $row['customer_id'] && (int) $bill['site_id'] === (int) $row['site_id']);
            $invoice = $invoiceRow ? $this->targetId('td_bill', $invoiceRow['bill_id']) : null;
            $amount = round((float) $row['transaction_amount'], 2);
            $date = $this->date($row['transaction_date']);
            $this->insert('td_customer_payment', $row['customer_payment_id'], 'legacy_customer_transactions', ['legacy_import_record_id' => $this->mapping('td_customer_payment', $row['customer_payment_id'])->id, 'customer_id' => $customer, 'invoice_id' => $invoice, 'company_reference' => (string) $row['site_id'], 'type' => $row['payment_type'] === 'out' ? 'receipt' : ($invoice ? 'invoice_charge' : 'opening_charge'), 'amount' => $amount, 'balance_due' => $row['remain_balance'], 'transaction_date' => $date, 'invoice_reference' => $row['bill_no'], 'payment_method' => $row['payment_mode'], 'external_reference' => $row['txn_number'] ?? null]);
            if ($row['payment_type'] !== 'out' || $amount <= 0) {
                continue;
            }
            if ($invoice) {
                if ($this->targetId('__receipt_payment__', $row['customer_payment_id'])) {
                    $this->skipped++;

                    continue;
                }
                $payment = $this->insert('__receipt_payment__', $row['customer_payment_id'], 'payments', ['reference' => 'LEGACY-PAY-'.$row['customer_payment_id'], 'invoice_id' => $invoice, 'currency' => 'INR', 'amount' => $amount, 'payment_date' => $date, 'method' => $this->paymentMethod($row['payment_mode']), 'external_reference' => $row['txn_number'] ?? null, 'notes' => 'Original receipt explicitly linked to legacy invoice '.$row['bill_no']]);
                $paid = DB::table('payments')->where('invoice_id', $invoice)->where('status', 'recorded')->sum('amount');
                $total = DB::table('invoices')->where('id', $invoice)->value('grand_total');
                DB::table('invoices')->where('id', $invoice)->update(['status' => $paid >= $total ? 'paid' : 'partially_paid']);
            } else {
                $this->insert('__receipt_wallet__', $row['customer_payment_id'], 'wallet_entries', ['customer_id' => $customer, 'currency' => 'INR', 'reference' => 'LEGACY-RECEIPT-'.$row['customer_payment_id'], 'direction' => 'credit', 'amount' => $amount, 'transaction_date' => $date, 'notes' => 'Unallocated legacy receipt #'.$row['customer_payment_id'].'; '.$row['payment_mode'].(! empty($row['txn_number']) ? '; '.$row['txn_number'] : '').'. Apply to an invoice using wallet payment.']);
            }
        }
    }

    private function paymentMethod(string $method): string
    {
        return match (strtoupper($method)) {
            'CASH' => 'cash', 'UPI' => 'upi', 'CHEQUE' => 'cheque', default => 'bank_transfer'
        };
    }

    private function importOtherData(): void
    {
        foreach ($this->rows['td_login_log'] ?? [] as $row) {
            $user = $this->targetId('td_users', $row['user_id']);
            $this->insert('td_login_log', $row['log_id'], 'activity_logs', ['actor_id' => $user, 'actor_name' => $user ? DB::table('users')->where('id', $user)->value('name') : 'Legacy user #'.$row['user_id'], 'subject_type' => 'user', 'subject_id' => $user ?? 0, 'event' => 'login', 'description' => 'Imported successful login from '.$row['ip_address'].'; browser: '.$row['last_browser_used'], 'created_at' => $this->date($row['last_login'])]);
        }
        foreach ($this->rows['td_contact_enquiry'] ?? [] as $row) {
            $this->insert('td_contact_enquiry', $row['enquiry_id'], 'enquiries', ['name' => mb_substr($row['name'], 0, 120), 'email' => $row['email'], 'company' => '', 'country' => 'India', 'phone' => mb_substr($row['mobile'], 0, 40), 'project_type' => 'legacy-service-'.$row['service_id'], 'description' => $row['description'], 'privacy_consent' => false, 'status' => 'new', 'created_at' => $this->date($row['created'])]);
        }
        foreach ($this->rows['td_expense'] ?? [] as $row) {
            $category = DB::table('expense_categories')->where('name', 'Legacy expenses')->value('id') ?? DB::table('expense_categories')->insertGetId(['name' => 'Legacy expenses', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            $this->insert('td_expense', $row['expense_id'], 'expenses', ['title' => mb_substr(strip_tags($row['expense_details']), 0, 255), 'notes' => $row['expense_details'], 'expense_category_id' => $category, 'amount' => $row['expense_amount'], 'currency' => 'INR', 'expense_date' => $this->date($row['expense_date']), 'created_at' => $this->date($row['created'])]);
        }
    }
}

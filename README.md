# Howvanta Technologies

Corporate website and the Phase 1 foundation of a private office workspace. Built with Laravel 13, Blade, Tailwind CSS 4, and Vite 8. Tagline: **Engineering What's Next.**

## Implemented

- Responsive navy/cyan homepage with original SVG geometry, nine services, engineering principles, delivery process, engagement models, and clearly labelled demonstration projects.
- About, services and service details, portfolio and project details, engagement, contact, privacy, terms, and custom 404 pages.
- Shared Blade layout, buttons, enquiry fields, service and project components, mobile menu, keyboard focus, skip link, and reduced-motion support.
- Enquiry persistence, Form Request validation, plain-text sanitization, consent, honeypot, CSRF protection, and rate limit of five submissions per IP per ten minutes.
- Optional enquiry email notification. The record is saved first; delivery failure is logged without personal data and does not discard the enquiry.
- Native Laravel session authentication, password hashing, session regeneration/invalidation, login throttling, an administrator gate, and private dashboard with real counts and paginated enquiry records.
- Named routes, canonical and Open Graph metadata, sitemap, robots policy, favicon, and security headers. Private responses use no-store and noindex headers.
- Secure interactive commands to create an administrator and reset a password. No seeded accounts or committed passwords.

## Requirements and detected environment

Detected: PHP **8.5.5**, Composer **2.9.5**, Node **26.10.0**, npm **11.19.1**. Installed framework: **Laravel 13.35.0** (locked in composer.lock). PHP 8.3+ is required by Laravel 13; use a currently supported PHP release compatible with all locked dependencies.

PHP and Composer are installed locally but are not on PATH:

```powershell
$env:PATH = 'G:\xampp_lite_8_5\apps\php;' + $env:PATH
php -v
php G:\xampp_lite_8_5\apps\php\composer\composer.phar --version
```

Use `npm.cmd` on this Windows host because PowerShell blocks `npm.ps1`. PHP requires standard Laravel extensions, including PDO, mbstring, XML, OpenSSL, ctype, fileinfo, and a database PDO driver. This host has pdo_mysql; its SQLite DLL is installed but needs explicit enabling for local verification.

## MySQL setup

Create a dedicated empty MySQL database and a user with appropriate privileges using your database administration tool. Do not use production data for tests. For a fresh checkout:

```powershell
php G:\xampp_lite_8_5\apps\php\composer\composer.phar install
Copy-Item .env.example .env
php artisan key:generate
npm.cmd ci
npm.cmd run build
```

Do not overwrite an existing `.env` or rotate a deployed APP_KEY. Configure your own credentials in `.env`:

```dotenv
APP_NAME="Howvanta Technologies"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=howvanta
DB_USERNAME=your_dedicated_database_user
DB_PASSWORD=your_local_password
SESSION_DRIVER=database
SESSION_ENCRYPT=true
CACHE_STORE=database
```

Then:

```powershell
php artisan config:clear
php artisan migrate --seed
php artisan howvanta:admin
php artisan serve --host=127.0.0.1 --port=8000
```

Visit http://localhost:8000 and sign in at `/login` to access `/admin`. Enter the administrator password in the hidden interactive prompt; it must contain at least 12 characters. There is no public registration. To reset an account through trusted server access: `php artisan howvanta:password`. Database sessions are revoked on reset; if using file sessions, clear session files during a maintenance window to revoke existing sessions.

For asset hot reload, run `npm.cmd run dev` in a second terminal while the Laravel server runs. Built assets are sufficient without a Vite dev server. Configure Apache's DocumentRoot to the application's **public/** directory, never the repository root. Enable rewrite support and use the supplied public/.htaccess.

## Current local database

The ignored local .env now uses the configured MySQL connection to database **howvanta**, with the application URL **http://localhost/b070b422c5d66d91c88b73b127d955d2**. The server reports MariaDB 11.4.10 through Laravel's MySQL driver. SQLite is used only for disposable automated tests; no test runs migrations against the local business database.

The local website works at **http://localhost/b070b422c5d66d91c88b73b127d955d2/**, without /public/ in the URL. No Apache restart is needed: the repository's root .htaccess routes requests to a small index.php gateway, which loads the standard Laravel public/index.php entry point. Public build assets, images, fonts, favicon, and robots.txt are served from public/. Hidden paths, private directories, configuration files, and other repository files are blocked; there is no blanket existing-file bypass. Do not remove these restrictions or enable directory listing.

An optional Apache alias is also prepared in apache-local.conf, installed at G:/xampp_lite_8_5/apps/apache/conf/extra/howvanta-local.conf, and included by httpd-vhosts.conf. Apache syntax validation passed, but its service restart required administrator elevation and was canceled. The local gateway works without that alias; its rules also allow public/.htaccess to take over if the alias is activated on a later restart. Production hosting should continue to use public/ as its document root.

## Migrations and seed data

- `0001_01_01_000000_create_users_table`: users, password reset tokens, sessions.
- `0001_01_01_000001_create_cache_table`: cache and cache locks.
- `0001_01_01_000002_create_jobs_table`: jobs, batches, failed jobs.
- `2026_10_13_000001_create_enquiries_table`: enquiry records and indexed staff/administrator role on users.

The original four migrations ran successfully on the configured MySQL connection. Five additive migrations now supply the remaining schema:

- create_office_management_tables: currencies, customers, projects, milestones, project history, expense categories, expenses, and private receipt metadata.
- create_billing_tables: document sequences, invoices, line items, payments, invoice history, and payment history.
- extend_enquiry_management_tables: assignment, follow-up and conversion links, soft deletion, and enquiry notes.
- create_website_content_tables: media assets, services, portfolio projects, pages, menus/items, settings, and social links.
- create_permissions_and_activity_tables: per-user module permissions, activity logs, and normalized field changes.

These migrations create **26 new tables** and extend enquiries without removing existing records. They seed only reference configuration: INR/USD/GBP/AED/KWD currencies and an invoice sequence starting at zero. No customer, invoice, payment, expense, or CMS content is fabricated. DatabaseSeeder creates no accounts or business records.

Money totals use DECIMAL(20,3), unit prices DECIMAL(20,6), quantities DECIMAL(12,4), and tax rates DECIMAL(7,4). Currency is explicit; invoice snapshots preserve billing details and rounding precision. Composite foreign keys prevent invoice/project customer mismatches and payment/invoice currency mismatches. Invoice numbers and payment references are unique. Payments remain separate; paid and outstanding amounts should be calculated from valid payment records rather than maintained as duplicate mutable columns.

Document sequence rows are intended to be locked inside a transaction when allocating a number; the database table alone does not implement concurrent allocation. Financial validation, totals, rounding, finalized-record immutability, cancellation rules, automated histories, staff policy enforcement, upload protection, and CMS HTML sanitization still require the corresponding application workflows. Restrictive foreign keys prevent removing financial parents with dependants; they do not prevent arbitrary SQL updates. The current website still reads its initial static content.

## Email and company configuration

Set `COMPANY_EMAIL` to a verified company address; the footer hides it when blank. Set `ENQUIRY_NOTIFICATION_EMAIL` and configure Laravel's MAIL_* environment variables for real notifications. The default log mailer does not deliver email. Notifications contain no enquiry personal data and direct the team to the private dashboard. Confirm delivery with the chosen mail provider before launch.

Public service descriptions are currently in `config/howvanta.php`, and page copy is in `resources/views`. CMS editing, logo uploads, social links, and navigation management are later-phase work. Do not add unverified company contact details, client logos, testimonials, or certifications.

## Verification

```powershell
php -d extension=pdo_sqlite vendor/phpunit/phpunit/phpunit
npm.cmd run build
php artisan view:cache
php artisan route:list --except-vendor
```

The suite uses in-memory SQLite and array sessions/cache/mail; it does not touch MySQL. Critical tests cover public rendering, enquiry storage and sanitization, invalid input, consent, honeypot, mass-assignment boundaries, enquiry and login throttling, guest/staff/admin access, login/logout, and escaped private output. Latest verified result: **18 tests, 89 assertions, zero failures**. Schema regression tests also cover composite foreign keys, unique financial references, partial-payment storage, invoice snapshots, deny-by-default permission rows, and rollback/reapplication preserving existing enquiries. The Vite production build passed. No browser-based visual or accessibility audit has been performed.

## Production deployment and maintenance

1. Supply verified contact details, database and SMTP credentials, and the real APP_URL. Review legal copy for the actual business's practices and obligations before launch.
2. Install with `composer install --no-dev --optimize-autoloader`; compile assets with `npm ci && npm run build` in your build environment and deploy public/build. Laravel Boost is a development-only tool.
3. Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://howvanta.com`, `SESSION_SECURE_COOKIE=true`, and `SESSION_ENCRYPT=true`. Terminate HTTPS correctly and configure trusted proxies if behind a load balancer. Keep secrets outside Git.
4. Back up the database before migrations; run `php artisan migrate --force`, then `php artisan config:cache`, `php artisan route:cache`, and `php artisan view:cache`. Provision the first administrator with the interactive command.
5. Restrict the web root to public/. Give the application write access only where needed (storage and bootstrap/cache). Never expose .env, private storage, logs, or database files. Configure HTTPS and server-level security headers.
6. Back up database and private storage, test restores, monitor logs and mail failures, apply security updates, and establish a retention/deletion process for enquiries. Use shared cache and session storage for multi-server deployments so throttling is consistent.
7. robots.txt allows public indexing while excluding private routes; protect staging with authentication or an environment-specific noindex policy. robots.txt is not an access-control mechanism.

## Remaining phases

This is Phase 1 with an initial authentication/dashboard shell and the remaining database schema, not the complete office management system. Next: database-backed CMS with publication controls and sanitized content; enquiry status/notes/follow-ups and deduplicated conversion; customers and projects with policies; configurable staff permissions; then invoices with transactional numbering, server-calculated decimal totals, immutable finalized records, payments, PDF output, expenses/private receipts, and financial audit history. No financial metrics or business records are fabricated.

Official references consulted: [Laravel 13 installation](https://laravel.com/docs/13.x/installation), [release compatibility](https://laravel.com/docs/13.x/releases), [authentication](https://laravel.com/docs/13.x/authentication), and [validation](https://laravel.com/docs/13.x/validation).

# Majra (مَجرى)

Majra is a Jordan-focused early warning and community reporting platform for flood risks, affected roads, alerts, and company area monitoring. It is built with Laravel 12, PHP 8.2+, Blade, and Vite.

## Requirements

- PHP 8.2 or later, with the Laravel-required extensions
- Composer
- Node.js and npm
- MySQL (XAMPP is suitable for local development)

## Local setup

Run these commands from the project root in PowerShell.

1. Start MySQL in XAMPP and create a database named `majra` in phpMyAdmin.
2. Create `.env` from the example only if you do not already have a local `.env` file. This preserves existing local settings and API keys:

   ```powershell
   if (-not (Test-Path .env)) { Copy-Item .env.example .env }
   ```

3. Install PHP dependencies and create the Laravel application key:

   ```powershell
   composer install
   php artisan key:generate
   ```

4. Check the database settings in `.env`. The defaults in `.env.example` are for a local XAMPP MySQL server (`127.0.0.1:3306`, database `majra`, username `root`, blank password). Update them if your local MySQL setup differs.
5. Create the database tables and seed the demo accounts and sample data:

   ```powershell
   php artisan migrate --seed
   php artisan storage:link
   ```

6. Install and build the frontend assets:

   ```powershell
   npm install
   npm run build
   ```

7. Start the Laravel server and open `http://127.0.0.1:8000`:

   ```powershell
   php artisan serve
   ```

For frontend development with hot reload, run `npm run dev` in a second terminal instead of `npm run build`.

## Demo accounts

All seeded demo accounts use this password:

```text
MajraDemo2026!
```

| Role | Email | Password |
| --- | --- | --- |
| Administrator | `admin@majra.demo` | `MajraDemo2026!` |
| Citizen | `citizen@majra.demo` | `MajraDemo2026!` |
| Company with an active demo subscription | `company@majra.demo` | `MajraDemo2026!` |
| Company with an expired demo subscription | `expired-company@majra.demo` | `MajraDemo2026!` |

These credentials are for local academic demonstrations only. Change or remove them before deploying the application publicly.

## Main workflows

- Citizens can view the map and reports, submit reports after signing in, and choose GPS to set a report location.
- Administrators can review and delete reports, manage alerts and road closures, manage citizens and company accounts, and manage subscription plans.
- Company accounts can manage monitored areas and view matching reports and road closures.
- Company deletion is blocked while its subscription is active. Financial records are retained for audit; archive a company with payment history instead of deleting it.
- Demo reports, alerts, and road closures are labeled as sample data and do not describe real-world conditions.

## Optional integrations

Keep API keys and payment secrets in the ignored local `.env` file. Never commit `.env` or paste secret values into this README.

- Gemini report analysis is optional. Set `GEMINI_API_KEY` in `.env`; manual review remains available when the integration is not configured.
- Stripe is optional and should use test credentials for local development: `STRIPE_KEY`, `STRIPE_SECRET`, and `STRIPE_WEBHOOK_SECRET`. Subscription activation is confirmed by the signed Stripe webhook, not by the checkout return page.
- Weather data uses Open-Meteo. Nearby hospital information uses OpenStreetMap/Overpass.

## Tests

Run the automated test suite with:

```powershell
php artisan test
```

## Useful commands

```powershell
php artisan migrate --seed  # Apply migrations and add demo data
php artisan db:seed         # Re-run the idempotent demo seeder
npm run dev                 # Start Vite hot reload
npm run build               # Build production frontend assets
```

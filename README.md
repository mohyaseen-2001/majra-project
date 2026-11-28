# Majra (مَجرى)

Majra is a Jordan-focused platform that brings flood reports, road closures, weather, and safety alerts onto one map. Citizens can report local hazards, administrators can review reports and update closures, and companies can monitor their operating areas. It is built with Laravel 12, PHP 8.2+, MySQL, Blade, Leaflet, and Vite.

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

3. Install PHP dependencies. Generate the Laravel application key only for a new installation with an empty `APP_KEY`:

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
   npm ci
   npm run build
   ```

7. Start the Laravel server and open [the local homepage](http://127.0.0.1:8000/):

   ```powershell
   php artisan serve
   ```

Set `APP_URL` in `.env` to the address printed by Laravel. If you start on another port, such as `php artisan serve --port=8002`, use that port in `APP_URL` and in the Stripe webhook forwarder.

For frontend development with hot reload, run `npm run dev` in a second terminal.

## Demo accounts

| Role | Email | Password |
| --- | --- | --- |
| Administrator | `admin@majra.demo` | `MajraDemo2026!` |
| Citizen | `citizen@majra.demo` | `MajraDemo2026!` |
| Company with an active demo subscription | `company@majra.demo` | `MajraDemo2026!` |
| Company with an expired demo subscription | `expired-company@majra.demo` | `MajraDemo2026!` |

These credentials are for local academic demonstrations only. Change or remove them before deploying the application publicly.

## Demo walkthrough

- Sign in as a citizen and open `/map` to view hazards, alerts, closures, weather, and nearby hospitals. Open `/reports/create` to submit a photo and select a point on the map or request one-time browser location access.
- Sign in as an administrator and open `/admin/reports` to inspect reports, review uncertain cases, and change publication decisions. The sidebar also links to road closures, alerts, accounts, companies, subscriptions, payments, contact messages, and subscription plans. The Blade management pages use Axios to call the protected REST endpoints under `/api/admin`.
- Sign in as a company and open `/company/dashboard`. An active paid plan unlocks monitored areas, matching reports and closures, and area alerts. Reports and closures match by distance when a center is supplied, and by area name otherwise. Alerts use the saved area names.
- Company deletion is blocked while its subscription is active. Financial records are retained for audit; archive a company with payment history instead of deleting it.
- Demo reports, alerts, and road closures are labeled as sample data and do not describe real-world conditions.
- The navigation controls switch between Arabic and English, switch between light and dark themes, and pause the weather background animation.

## Optional integrations

Keep API keys and payment secrets in the ignored local `.env` file. Never commit `.env` or paste secret values into this README.

- **Gemini** uses `GEMINI_API_KEY` and defaults to `gemini-3.1-flash-lite`. Clear flood reports can be published automatically. A clear image without a visible hazard can be declined. Inconclusive results and provider failures go to manual review.
- **Stripe** uses a test-mode `STRIPE_SECRET` and a `STRIPE_WEBHOOK_SECRET`. Start the listener in the same Stripe test account and save its signing secret in `.env`.

  ```powershell
  stripe listen --events checkout.session.completed --forward-to http://127.0.0.1:8000/api/stripe/webhook
  ```

  Keep the listener running during checkout. An accepted signed payment event activates the plan for its configured period. New paid plans use a one-time payment per period, and companies can choose another plan after the current paid period ends.

- Weather data uses Open-Meteo. Nearby hospital locations are matched locally against a snapshot of the [HOT Jordan Health Facilities export](https://data.humdata.org/dataset/hotosm_jor_health_facilities). The bundled data is dated 2026-11-03 and is licensed under [ODbL 1.0 by OpenStreetMap contributors](https://www.openstreetmap.org/copyright). The snapshot uses named facilities tagged as hospitals and represents polygons with the average of their exterior coordinates.
- Local password reset and verification emails use the log mailer by default. Check `storage/logs/laravel.log` for messages, or configure a mail provider to deliver email.

After changing environment settings, run `php artisan config:clear` if Laravel is using cached configuration.

## Tests

Build the frontend assets before running the automated test suite:

```powershell
npm run build
php artisan test
```

## Useful commands

```powershell
php artisan migrate --seed  # Apply migrations and add demo data
php artisan db:seed         # Re-run the idempotent demo seeder
php artisan schedule:work   # Run scheduled tasks, including report expiration
npm run dev                 # Start Vite hot reload
npm run build               # Build production frontend assets
```

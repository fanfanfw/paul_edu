# Skill Digital Marketplace MVP

Laravel 12 marketplace kelas digital untuk mentor dan user. Mentor dapat membuat kelas, mengelola section/lesson/material private, dan menerima pendapatan dummy. User dapat topup saldo dummy, membeli/enroll kelas, mengakses materi melalui protected route, dan memberi review. Admin melakukan monitoring dan moderation melalui Filament.

## Tech Stack

- Laravel 12
- Blade + Livewire + Volt
- Filament v4 admin panel
- Spatie Laravel Permission
- PostgreSQL
- Redis optional for cache/queue support
- Tailwind CSS and Vite
- Docker Compose with PHP-FPM, Nginx, PostgreSQL, Redis, queue worker, scheduler

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
php artisan migrate --seed
npm run build
php artisan serve
```

Run tests:

```bash
php artisan test
```

If running migrations from the host against Docker PostgreSQL, use a host-reachable database host:

```env
DB_HOST=127.0.0.1
DB_PORT=5432
```

Inside Docker, `DB_HOST=postgres` is correct because the hostname resolves on the Compose network.

## Docker Setup

Validate Compose config:

```bash
docker compose config
```

Start services:

```bash
docker compose up -d --build
```

Run app commands in the app container:

```bash
docker compose exec app php artisan migrate --seed
docker compose exec app php artisan test
```

Build frontend assets on the host machine, because the current PHP app image does not install Node or NPM:

```bash
npm install
npm run build
```

The app is served by Nginx on port `8080` by default. PostgreSQL is published on `5432`; if local PostgreSQL already uses that port, adjust the host port in `docker-compose.yml`.

## Demo Accounts

Seeded accounts use password `password`.

- Admin: `admin@example.com`
- Mentor: `mentor@example.com`
- User: `user@example.com`

Seeders create roles/permissions, platform setting, platform wallet, demo users, categories, demo courses, course section/lesson metadata, and a tiny private demo PDF material for the free demo course. Seeders are intended to be idempotent.

## Key Routes

- `/` landing page
- `/courses` public catalog
- `/courses/{course:slug}` public course detail
- `/dashboard` role-aware dashboard
- `/my-courses` enrolled courses
- `/wallet` dummy wallet topup
- `/transactions` wallet ledger history
- `/learn/{course:slug}` learning page for active enrollments
- `/materials/{material}/view` protected private material viewer
- `/mentor/dashboard` mentor dashboard
- `/mentor/courses` mentor course management
- `/mentor/courses/{course}/materials` mentor material manager
- `/mentor/wallet` mentor wallet income view
- `/mentor/sales` mentor sales list
- `/admin` Filament admin panel

## Dummy Wallet Rules

- Topup is dummy and succeeds immediately.
- There is no real payment gateway.
- There is no payout flow.
- There is no refund flow.
- Wallet balances must be changed through `WalletService`.
- Wallet ledger rows store before/after balances and are treated as immutable in app flow.
- Platform wallet uses `owner_type = platform` and `owner_id = 0`.
- Human users and mentors use `owner_type = user`.

## Course Purchase And Commission

- Free courses create an order with total `0` and active enrollment.
- Paid courses debit buyer wallet and credit mentor/platform wallets atomically.
- Mentor commission is controlled by platform setting `mentor_commission_rate`.
- Existing orders keep price and commission snapshots even if course price or commission changes later.

## Private Material Storage

- Course material files are stored on `Storage::disk('course_materials')`.
- Material files are not stored on the public disk.
- Material files are not exposed through public storage URLs.
- Access uses the protected `/materials/{material}/view` route and active enrollment checks.
- This protects direct public access but is not DRM and cannot prevent screen recording or browser-level copying.

## Admin Panel

Filament resources include monitoring and moderation for:

- Courses
- Course categories
- Orders
- Wallets
- Wallet transactions
- Course reviews
- Platform setting `mentor_commission_rate`

Admin dashboard includes lightweight platform stats. No payout, refund, bank account, voucher, subscription, affiliate, certificate, quiz, chat, or detailed progress tracking exists in this MVP.

## Verification Commands

```bash
php artisan test
DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan migrate:fresh --seed --force
npm run build
docker compose config
```

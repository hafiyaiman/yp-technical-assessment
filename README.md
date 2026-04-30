# Laravel 11 Breeze Livewire TallStack UI

Fresh Laravel 11 application scaffolded with Breeze, Livewire, TallStack UI v3, Tailwind CSS 4, Pest, MySQL configuration for TablePlus-driven local development, and Cloudflare R2 S3-compatible storage.

## Stack

- Laravel 11
- Laravel Breeze with the Livewire stack
- Livewire 3 and Volt
- TallStack UI v3
- Tailwind CSS 4 through `@tailwindcss/vite`
- Pest for tests
- MySQL for the application database
- Cloudflare R2 for object storage

## Local Setup

Install PHP dependencies:

```bash
composer install
```

Install frontend dependencies:

```bash
npm install
```

Copy the environment file and generate an app key:

```bash
cp .env.example .env
php artisan key:generate
```

Automated tests use SQLite in memory through `phpunit.xml`, so tests do not require MySQL or R2 credentials.

## MySQL And TablePlus

`.env.example` is set up for a local MySQL database that can be created and managed in TablePlus:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=yp_technical_assessment
DB_USERNAME=root
DB_PASSWORD=
```

Create the database in TablePlus with the same database name, then update `.env` with your local MySQL username and password.

This machine's current PHP extension list does not show `pdo_mysql`. Enable/install the PHP MySQL PDO extension before running Laravel migrations against MySQL.

After MySQL credentials are ready, run:

```bash
php artisan migrate
```

## Cloudflare R2 Storage

Cloudflare R2 is exposed as the Laravel `r2` disk and selected by default in `.env.example`:

```dotenv
FILESYSTEM_DISK=r2
CLOUDFLARE_R2_ACCESS_KEY_ID=<r2-access-key-id>
CLOUDFLARE_R2_SECRET_ACCESS_KEY=<r2-secret-access-key>
CLOUDFLARE_R2_REGION=auto
CLOUDFLARE_R2_BUCKET=<bucket-name>
CLOUDFLARE_R2_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
CLOUDFLARE_R2_URL=
CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT=false
CLOUDFLARE_R2_VISIBILITY=private
```

Use an R2 API token with object read/write access to the target bucket. If you configure a public bucket domain, place it in `CLOUDFLARE_R2_URL`; otherwise leave it blank.

Once credentials are present, a quick storage health check can be done with:

```bash
php artisan tinker
```

```php
Storage::disk('r2')->put('health-check.txt', 'ok');
Storage::disk('r2')->get('health-check.txt');
Storage::disk('r2')->delete('health-check.txt');
```

## Roles, Permissions, And Authentication

The first portal module is implemented with Breeze authentication, email OTP login verification, and database-backed roles and permissions.

Seeded roles:

- `lecturer`: can manage students, classes, subjects, and exams.
- `student`: can take exams and view own results.

Seeded demo accounts:

```text
lecturer@example.com / password
student@example.com / password
```

Role and permission checks are available through:

```php
$user->hasRole('lecturer');
$user->hasPermission('manage-exams');
```

Routes can be protected with middleware aliases:

```php
Route::middleware('role:lecturer')->group(...);
Route::middleware('permission:manage-exams')->group(...);
```

Current protected route examples:

- `/lecturer/dashboard`
- `/student/dashboard`

### Email OTP With Mailpit

Login uses a two-step flow:

1. The user enters email and password.
2. The system emails a six-digit OTP.
3. The user enters the OTP at `/login/otp` to finish authentication.

Local email is configured for Mailpit:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
```

Open the Mailpit UI, usually at `http://127.0.0.1:8025`, to read the OTP email. OTP codes expire after 10 minutes, and a pending code allows up to 5 failed attempts.

## TallStack UI MCP

The project includes `.mcp.json` for tools that support project-local MCP configuration:

```json
{
    "mcpServers": {
        "tallstackui": {
            "type": "http",
            "url": "https://tallstackui.com/mcp/tallstackui"
        }
    }
}
```

## Development

Run the app and Vite separately:

```bash
php artisan serve
npm run dev
```

Build assets:

```bash
npm run build
```

Run tests:

```bash
php artisan test
```

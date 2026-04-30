# Laravel 11 Breeze Livewire TallStack UI Supabase

Fresh Laravel 11 application scaffolded with Breeze, Livewire, TallStack UI v3, Tailwind CSS 4, Pest, and Supabase-ready PostgreSQL plus S3-compatible storage configuration.

## Stack

- Laravel 11
- Laravel Breeze with the Livewire stack
- Livewire 3 and Volt
- TallStack UI v3
- Tailwind CSS 4 through `@tailwindcss/vite`
- Pest for tests
- Supabase Postgres and Storage via environment variables

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

For local tests without Supabase credentials, keep `.env` on SQLite or rely on the SQLite in-memory values in `phpunit.xml`.

## Supabase Database

`.env.example` is set up for the common Supabase Session Pooler connection mode:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=aws-0-<region>.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.<project-ref>
DB_PASSWORD=<database-password>
DB_SSLMODE=require
```

After replacing the placeholders with real Supabase values, run:

```bash
php artisan migrate
```

## Supabase Storage

Supabase Storage is exposed as the Laravel `supabase` disk and selected by default:

```dotenv
FILESYSTEM_DISK=supabase
SUPABASE_STORAGE_ACCESS_KEY_ID=<storage-access-key-id>
SUPABASE_STORAGE_SECRET_ACCESS_KEY=<storage-secret-access-key>
SUPABASE_STORAGE_REGION=<project-region>
SUPABASE_STORAGE_BUCKET=<bucket-name>
SUPABASE_STORAGE_ENDPOINT=https://<project-ref>.storage.supabase.co/storage/v1/s3
SUPABASE_STORAGE_URL=
SUPABASE_STORAGE_USE_PATH_STYLE_ENDPOINT=true
SUPABASE_STORAGE_VISIBILITY=private
```

Once credentials are present, a quick storage health check can be done with:

```bash
php artisan tinker
```

```php
Storage::disk('supabase')->put('health-check.txt', 'ok');
Storage::disk('supabase')->get('health-check.txt');
Storage::disk('supabase')->delete('health-check.txt');
```

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

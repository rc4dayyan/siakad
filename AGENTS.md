# AGENTS.md

## Project overview

This repository is a Laravel 11 academic information system (SIAKAD) written for PHP 8.2+. It serves public pages plus separate staff, lecturer (`dosen`), and student (`mahasiswa`) areas. The UI is primarily Blade, with a small Vite entry point and a large set of prebuilt assets under `public/dist`.

Keep changes focused on the requested behavior. Preserve the existing Indonesian domain vocabulary, route names, and user-facing language unless the task explicitly asks for broader cleanup or translation.

## Repository map

- `app/Http/Controllers/Admin`: staff/admin features, grouped further by domain.
- `app/Http/Controllers/Dosen`: lecturer authentication and academic workflows.
- `app/Http/Controllers/Mahasiswa`: student authentication and workflows.
- `app/Http/Controllers/Root`: public website pages.
- `app/Http/Controllers/Services`: shared AJAX, import, and export endpoints.
- `app/Http/Middleware`: access checks for staff roles, lecturers, students, and active accounts.
- `app/Models`: Eloquent models. Some existing model class names are not StudlyCase; follow the local model name when touching existing code.
- `routes/web.php`: public/authentication routes and includes for every role-specific route file.
- `routes/route-global.php`: staff routes reused inside each staff department prefix.
- `routes/route-*.php`: department-, lecturer-, and student-specific routes.
- `resources/views`: Blade templates split across public/root, staff/user, lecturer, student, and shared base layouts.
- `resources/css/app.css` and `resources/js/app.js`: Vite entry points.
- `public/dist`: mostly third-party/prebuilt frontend assets plus tracked custom assets. Do not reformat or replace vendored files unless explicitly required.
- `database/migrations`, `database/seeders`, and `database/factories`: schema and seed data.
- `tests/Feature` and `tests/Unit`: PHPUnit test suites.

## Setup and common commands

Requirements: PHP 8.2+, Composer, Node.js/npm, and MySQL 8 or MariaDB 10.5+.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
```

Run the application and frontend compiler in separate terminals:

```bash
php artisan serve
npm run dev
```

Useful verification commands:

```bash
php artisan test
php artisan test --filter=TestName
./vendor/bin/pint --test
npm run build
php artisan route:list
```

Use the smallest relevant test command during iteration, then expand verification in proportion to the change. Run Pint only on changed PHP files when repository-wide formatting would create unrelated churn, for example:

```bash
./vendor/bin/pint --test app/Http/Controllers/ExampleController.php
```

## Safety rules

- Never commit `.env`, credentials, Midtrans keys, mail credentials, Turnstile secrets, or generated private data.
- Do not run `seed.sh`, `seed.bat`, `php artisan migrate:refresh`, `migrate:fresh`, or any database import against an existing database without explicit confirmation. The seed shortcuts erase and rebuild tables.
- `phpunit.xml` does not enable its commented-out in-memory SQLite settings. Before running database-writing tests, ensure `DB_CONNECTION` and `DB_DATABASE` point to a dedicated test database.
- Do not edit dependencies in `vendor`, `node_modules`, or third-party files in `public/dist/assets/extensions`.
- Do not commit generated Vite output in `public/build`, the `public/storage` symlink, caches, logs, uploaded files, or local archives.
- Preserve unrelated local changes and avoid broad mechanical rewrites.

## Implementation conventions

### PHP and Laravel

- Follow the style of nearby code while keeping new PHP compatible with Laravel 11 and PHP 8.2.
- Format changed PHP with Laravel Pint. Prefer explicit imports and standard Laravel/Eloquent patterns in new code.
- Validate all user input before using it. Keep validation messages in Indonesian when the surrounding screen is Indonesian.
- Use named routes and the existing route-name prefixes. Match existing HTTP verbs: `GET` for reads/pages, `POST` for creates/actions, `PATCH` for updates, and `DELETE` for deletes.
- Keep authorization at both the route/middleware boundary and, where records are user-owned, in the controller/query. Never trust a route parameter alone to prove ownership.
- Preserve existing public identifiers such as `code` and `slug`; do not expose sequential IDs in a new endpoint when adjacent features use codes.
- Use Eloquent relationships/query scopes where practical. Avoid silently changing accessor behavior such as `User::type`, `raw_type`, or phone normalization.
- Wrap multi-model writes and payment/accounting state changes in database transactions.
- Treat import/export, uploads, authentication, and payment callbacks as high-risk paths: validate file type/size, sanitize names, handle failures, and avoid logging secrets or sensitive personal data.

### Routes and access areas

Staff routes are mounted under department prefixes and guarded by `user-access:<role>`; active-only functionality is additionally wrapped in `is-active:1`. Shared staff functionality belongs in `route-global.php` only when every included department should receive it. Otherwise place it in the matching department file.

The established prefixes are:

- `web-admin` → Web Administrator
- `admin` → Departement Admin
- `academic` → Departement Academic
- `finance` → Departement Finance
- `officer` → Departement Officer
- `support` → Departement Support
- `dosen` → lecturer area guarded by `dsn-access`
- `mahasiswa` → student area guarded by `mhs-access`

When adding or changing routes, check for duplicate URIs/names and verify the result with a filtered `php artisan route:list` command.

### Blade and frontend assets

- Reuse layouts and partials under `resources/views/base` before introducing new markup structures.
- Keep Blade templates thin; move database access and business logic into controllers/services/models.
- Include CSRF protection in forms and use `@method('PATCH')` or `@method('DELETE')` when required.
- Escape user-controlled output with `{{ }}`. Use `{!! !!}` only for content that is intentionally trusted or sanitized.
- Prefer changes in `resources/css` and `resources/js`, then build with Vite. Edit `public/dist/custom` only when the feature already loads those tracked custom files directly.
- Preserve the existing Bootstrap/dashboard conventions and responsive behavior unless a redesign is requested.

### Database changes

- Add a new migration for schema changes; do not edit an already-deployed migration merely to change production state.
- Make migrations reversible and specify column attributes/indexes needed by the corresponding queries.
- Update affected model casts/relationships, factories, seeders, validation, and tests with the schema.
- Seeders should be safe for development/testing and must not contain real credentials or personal data.

## Testing expectations

- Add or update a focused Feature test for route, validation, authorization, database, upload, or workflow changes.
- Add Unit tests for isolated domain logic that does not need the framework/database.
- Cover the allowed path and at least one important rejection path, especially cross-role or cross-user access.
- For fixes, prefer a regression test that fails before the fix and passes afterward.
- Do not claim full verification when tests were skipped because services or a safe test database were unavailable; report exactly what was run.

## Before handing off

Review the diff for accidental generated files or secrets, run the relevant tests and formatter, and run `npm run build` when frontend source changed. Summarize changed behavior, list verification performed, and call out any migration, environment, or deployment step the user must take.

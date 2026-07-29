# Laravel Inertia Starter

Starter project with Laravel, Inertia.js, Vue 3, Tailwind CSS, Fortify auth, and a modern landing page.

## Tech stack

- Laravel 13
- PHP 8.3+
- Inertia.js + Vue 3
- Tailwind CSS 4
- Vite
- Laravel Fortify auth
- SQLite default database
- Spatie permission + activity log packages
- @lucide/vue icons

## Features

- Public landing page at `/`
- Authentication pages using Fortify
- Protected dashboard route at `/dashboard`
- User management list at `/users` for authenticated admin/HRD users
- Responsive mobile preview with bottom navigation
- WhatsApp contact CTA in landing page footer
- Default SQLite setup for fast local development
- Tailwind + modern card / hero styling

## Requirements

- PHP 8.3+
- Composer
- Node.js 18+ / npm
- SQLite (or optionally MySQL if you update env)

## Installation

```bash
cd c:/wamp64/www/laravel-inertia-starter
composer install
npm install
```

## Environment

Copy the example env file and configure the app key:

```bash
cp .env.example .env
php artisan key:generate
```

By default the project uses SQLite.

If you want SQLite locally, create the file:

```bash
mkdir -p database
copy NUL database\database.sqlite
```

Then ensure `.env` contains:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

## Database

Run migrations and seed the default user:

```bash
php artisan migrate
php artisan db:seed
```

## Run locally

Start the Laravel backend:

```bash
php artisan serve
```

Then start the Vite dev server:

```bash
npm run dev
```

- Laravel backend default: `http://127.0.0.1:8000`
- Vite dev server default: `http://127.0.0.1:5173` (may fall back to `5174` if port is busy)

## Scripts

- `npm run dev` - start Vite development server
- `npm run build` - build frontend assets
- `npm run lint` - lint frontend code with ESLint
- `npm run types:check` - run Vue type check

- `composer install` - install PHP dependencies
- `php artisan migrate` - run database migrations
- `php artisan db:seed` - seed database
- `php artisan serve` - run local Laravel server

## Main routes

- `/` - public landing page (`resources/js/pages/Welcome.vue`)
- `/dashboard` - authenticated dashboard
- `/users` - authenticated user management list (`resources/js/pages/Users/Index.vue`)
- `/login` - Fortify login
- `/settings/profile` - authenticated profile settings

## Notes

- `routes/web.php` currently maps `/` to the `Welcome` Inertia page.
- The app uses `@inertiajs/vue3` and `laravel-vite-plugin`.
- The starter includes a polished mobile navigation preview and a clean landing layout.

## Optional cleanup

If you want a completely clean starter, remove unused docs or components and adjust the pages in `resources/js/pages/`.

## Contact

Project adapted for GR Tech starter usage.

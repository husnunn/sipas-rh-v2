# SIPAS RH V1

Aplikasi manajemen SDM berbasis Laravel 13 + Inertia Vue 3.

## Tech Stack

- PHP 8.5
- Laravel 13
- Inertia.js v3 + Vue 3
- Tailwind CSS v4
- MySQL

## Kebutuhan Sistem

- PHP >= 8.5
- Composer
- Node.js + npm
- MySQL

## Instalasi

1. Clone repository ini
2. Install dependency backend:

```bash
composer install
```

3. Install dependency frontend:

```bash
npm install
```

4. Buat file environment:

```bash
cp .env.example .env
```

5. Generate app key:

```bash
php artisan key:generate
```

6. Atur konfigurasi database di `.env`, lalu jalankan migrasi:

```bash
php artisan migrate
```

## Menjalankan Aplikasi

Jalankan backend + frontend development server:

```bash
composer run dev
```

Atau jalankan terpisah:

```bash
php artisan serve
npm run dev
```

## Menjalankan Test

```bash
php artisan test --compact
```

## Build Asset Production

```bash
npm run build
```

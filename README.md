# Pulse

A personal dashboard for tracking daily life data in one place.

## What it tracks

- **Health** — daily step counts, heart rate, streaks, and statistics
- **Finance** — expenses and spending trends
- **Music** — Spotify listening history and recently played tracks
- **Gaming** — PlayStation session history, playtime per game, and presence status

## Tech stack

| Layer | Choice |
|---|---|
| Backend | PHP 8.3 / Laravel 12 |
| Frontend bundler | Vite 6 |
| CSS framework | Tailwind CSS v4 |
| Custom styling | SCSS + BEM |
| JS interactivity | Alpine.js v3 |
| Icons | Heroicons (SVG sprite) |
| Testing | Pest v4 |

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run dev
```

# Pulse

A personal dashboard for tracking health, media, gaming, music, and people — all in one place.

---

## Features

- **Health** — log daily steps, set goals, view streaks and yearly stats
- **Movies & TV** — track what you watch, manage cast exclusions, explore stats
- **Music** — artists, albums, tracks, and obsession tracking
- **Gaming** — PlayStation and Steam libraries with session logging and backlogs
- **People** — contacts with relationships, birthdays, and notes
- **Calendar** — events and work schedules
- **Global search** — search across all modules with `Ctrl+F`

## Tech stack

| Layer | Choice |
|---|---|
| Backend | PHP 8.3+ / Laravel 13 |
| Frontend bundler | Vite 8 |
| CSS framework | Tailwind CSS v4 |
| Custom styling | SCSS + BEM |
| JS interactivity | Alpine.js v3 |
| Charts | Chart.js v4 |
| Testing | Pest v4 |
| Code formatter | Laravel Pint |
| Static analysis | PHPStan / Larastan (level 8) |

## Setup

```bash
git clone <repo-url> pulse
cd pulse
composer run setup
```

The `setup` script handles everything: `composer install`, `.env` copy, key generation, migrations, `npm install`, and a production build.

## Development

```bash
composer run dev
```

Starts Laravel, the queue worker, Pail log viewer, and Vite — all concurrently.

## Other commands

```bash
composer run test      # Run Pest test suite
composer run lint      # Format with Laravel Pint
composer run analyse   # Static analysis with PHPStan
npm run build          # Production frontend build
```

## Environment

Copy `.env.example` to `.env` and adjust:

- `DB_*` — MySQL connection details
- `APP_TIMEZONE` — defaults to `Europe/Amsterdam`

---

<details>
<summary>🇳🇱 Nederlandse versie</summary>

## Over Pulse

Een persoonlijk dashboard voor het bijhouden van gezondheid, media, gaming, muziek en contacten — alles op één plek.

---

## Functionaliteiten

- **Gezondheid** — dagelijkse stappen loggen, doelen instellen, streaks en jaarstatistieken bekijken
- **Films & series** — bijhouden wat je kijkt, castuitsluitingen beheren, statistieken verkennen
- **Muziek** — artiesten, albums, nummers en obsession tracking
- **Gaming** — PlayStation- en Steam-bibliotheken met sessieregistratie en backlogs
- **Mensen** — contacten met relaties, verjaardagen en notities
- **Agenda** — evenementen en werkroosters
- **Globale zoekfunctie** — zoeken door alle modules met `Ctrl+F`

## Tech stack

| Laag | Keuze |
|---|---|
| Backend | PHP 8.3+ / Laravel 13 |
| Frontend bundler | Vite 8 |
| CSS framework | Tailwind CSS v4 |
| Aangepaste stijlen | SCSS + BEM |
| JS interactie | Alpine.js v3 |
| Grafieken | Chart.js v4 |
| Testing | Pest v4 |
| Code formatter | Laravel Pint |
| Statische analyse | PHPStan / Larastan (level 8) |

## Installatie

```bash
git clone <repo-url> pulse
cd pulse
composer run setup
```

Het `setup`-script regelt alles: `composer install`, `.env` kopiëren, sleutelgeneratie, migraties, `npm install` en een productiebuild.

## Ontwikkeling

```bash
composer run dev
```

Start Laravel, de queue worker, Pail log viewer en Vite — allemaal tegelijk.

## Overige commando's

```bash
composer run test      # Pest-testsuite uitvoeren
composer run lint      # Formatteren met Laravel Pint
composer run analyse   # Statische analyse met PHPStan
npm run build          # Productiebuild van de frontend
```

## Omgeving

Kopieer `.env.example` naar `.env` en pas aan:

- `DB_*` — MySQL-verbindingsgegevens
- `APP_TIMEZONE` — standaard `Europe/Amsterdam`

</details>

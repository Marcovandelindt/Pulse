# Pulse

A personal dashboard that brings daily life data together in one place. Built as a self-hosted Laravel application with a dark-mode-first design.

---

## Features

### Dashboard
Central overview that surfaces the most relevant data at a glance — recent activity, key stats, and what's currently playing.

### Health
Log and track daily health metrics:
- **Steps** — daily step count logging with streak tracking
- **Heart rate** — average and resting heart rate per day
- **Statistics** — weekly and monthly trend views
- **Step goals** — set custom daily targets and track progress against them
- **Data export** — export health entries to CSV

### Calendar
A full monthly calendar with personal scheduling:
- **Events** — create, edit, and delete one-off events with title, type, notes, and optional time
- **Event types** — Event, Appointment, Important Event, Holiday, Reminder (each with their own color)
- **Recurrence** — repeat events weekly, monthly, or yearly with an optional end date
- **Work schedules** — define recurring weekly work shifts (e.g. Mon–Fri 06:00–14:30) that appear as pills on each matching day; supports multiple shifts per day, valid from/until dates, and an active toggle
- **Contact integration** — birthdays, anniversaries, and other important contact dates appear automatically on the calendar

### Music
Synced from Spotify:
- **Listening history** — full play log with track, artist, album, and timestamp
- **Track browser** — browse all listened tracks with play counts
- **Artist & album views** — detailed pages per artist and album
- **Statistics** — top tracks, artists, and albums over time
- **Obsession tracking** — mark tracks as current obsessions to highlight them

### Gaming
#### PlayStation
- **Session log** — automatic session history pulled from PlayStation Network
- **Per-game stats** — total playtime, last played, session count
- **Trophies** — trophy breakdown per game (bronze, silver, gold, platinum)
- **Categories** — organise games into custom categories
- **Backlog** — track games yet to be played
- **Stats overview** — total playtime, most played, recent activity

#### Steam
- Browse Steam library with playtime data

### Media
Track movies and TV series via TMDB integration:
- **Movies** — log watches with personal rating and notes
- **TV series** — track episodes watched per season, personal ratings, favourite flag
- **Watched runtime** — total watch time across all movies and series

### People
A personal contact book:
- **Contacts** — store name, birthdate, notes, and a profile photo
- **Important dates** — attach custom dates (anniversaries, milestones) that appear on the calendar
- **Relationships** — link contacts to each other with a relationship type and optional date
- **Death dates** — mark deceased contacts; their anniversary appears subtly on the calendar

### Settings
- **Relationship types** — manage the list of relationship labels used when linking contacts

---

## Tech stack

| Layer | Choice |
|---|---|
| Backend | PHP 8.3 / Laravel 12 |
| Frontend bundler | Vite 6 |
| CSS framework | Tailwind CSS v4 |
| Custom styling | SCSS + BEM |
| JS interactivity | Alpine.js v3 |
| Icons | Heroicons (SVG) |
| Testing | Pest v4 |
| Code style | Laravel Pint |
| Static analysis | PHPStan level 8 |

### External integrations

| Service | Used for |
|---|---|
| Spotify API | Music listening history sync |
| PlayStation Network | Gaming session and trophy data |
| Steam API | Game library and playtime |
| TMDB | Movie and TV series metadata |

---

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run dev
```

Configure your external API credentials in `.env` to enable the integrations (Spotify, PlayStation, Steam, TMDB).

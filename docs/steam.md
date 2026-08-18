# Feature: Steam (Gaming Library)

Steam-bibliotheek bijhouden via de Steam Web API. Spellen synchroniseren, speeltijd inzien,
backlog beheren, ratings bijhouden en cost-per-hour analyses bekijken.

---

## Inhoudsopgave

1. [Authenticatie & configuratie](#1-authenticatie--configuratie)
2. [Database schema](#2-database-schema)
3. [Models](#3-models)
4. [Enums](#4-enums)
5. [SteamApiService](#5-steamapiservice)
6. [Controllers & Routes](#6-controllers--routes)
7. [Views](#7-views)
8. [Omgevingsvariabelen](#8-omgevingsvariabelen)
9. [Implementatievolgorde](#9-implementatievolgorde)

---

## 1. Authenticatie & configuratie

Geen OAuth. De app gebruikt een **directe API-key benadering** — de installatie werkt voor één Steam-account.

1. Gebruiker haalt een Steam API key op via `https://steamcommunity.com/dev/apikey`
2. Gebruiker stelt het Steam ID in (handmatig, of via de vanity URL resolver in de service)
3. Beide waarden komen uit `.env` via `config/services.php`

```php
// config/services.php
'steam' => [
    'api_key'  => env('STEAM_API_KEY'),
    'steam_id' => env('STEAM_ID'),
],
```

---

## 2. Database schema

### `steam_games`

| Kolom                    | Type              | Eigenschappen                   |
|--------------------------|-------------------|---------------------------------|
| `id`                     | bigint            | primary key                     |
| `steam_appid`            | bigint            | unique, not null                |
| `name`                   | string            | not null                        |
| `image_url`              | string            | nullable                        |
| `playtime_minutes`       | unsigned int      | default 0                       |
| `playtime_2weeks_minutes`| unsigned int      | nullable                        |
| `last_played_at`         | timestamp         | nullable                        |
| `price`                  | decimal(8,2)      | nullable (handmatige invoer)    |
| `backlog_status`         | string            | nullable (enum waarde)          |
| `play_mode`              | string            | nullable (enum waarde)          |
| `main_story_completed`   | boolean           | default false                   |
| `user_rating`            | unsigned tinyint  | nullable (1–10)                 |
| `critic_rating`          | unsigned tinyint  | nullable (1–100)                |
| `created_at`             | timestamp         |                                 |
| `updated_at`             | timestamp         |                                 |

Indexes: `playtime_minutes`, `last_played_at`

---

### `genres`

| Kolom        | Type      | Eigenschappen |
|--------------|-----------|---------------|
| `id`         | bigint    | primary key   |
| `name`       | string    | not null      |
| `created_at` | timestamp |               |
| `updated_at` | timestamp |               |

---

### `genre_steam_game` (pivot)

| Kolom          | Type   | Eigenschappen                         |
|----------------|--------|---------------------------------------|
| `id`           | bigint | primary key                           |
| `genre_id`     | bigint | FK → genres (cascadeOnDelete)         |
| `steam_game_id`| bigint | FK → steam_games (cascadeOnDelete)    |
| `created_at`   | timestamp |                                    |
| `updated_at`   | timestamp |                                    |

Unique: `[genre_id, steam_game_id]`

---

## 3. Models

### `SteamGame`

```php
protected function casts(): array
{
    return [
        'last_played_at'       => 'datetime',
        'main_story_completed' => 'boolean',
        'backlog_status'       => BacklogStatus::class,
        'play_mode'            => PlayMode::class,
    ];
}

public function genres(): BelongsToMany   // → Genre via genre_steam_game

// Scopes
public function scopeMostPlayed(Builder $query, int $limit = 10): Builder
    // ORDER BY playtime_minutes DESC, LIMIT $limit

public function scopeRecentlyPlayed(Builder $query, int $limit = 10): Builder
    // ORDER BY last_played_at DESC, LIMIT $limit

public function scopePlayedRecently(Builder $query): Builder
    // WHERE playtime_2weeks_minutes > 0

public function scopeNeverPlayed(Builder $query): Builder
    // WHERE playtime_minutes = 0

// Via HasBacklogStatus trait:
public function scopeInBacklog(Builder $query): Builder
public function scopeWantToPlay(Builder $query): Builder
public function scopePlaying(Builder $query): Builder
public function scopeCompleted(Builder $query): Builder
public function scopeDropped(Builder $query): Builder

// Accessors
public function playtimeHours(): Attribute      // playtime_minutes / 60
public function formattedPlaytime(): Attribute  // bijv. "25u 30m"
public function playtime2weeksHours(): Attribute
public function steamUrl(): Attribute           // https://store.steampowered.com/app/{steam_appid}
```

---

### `Genre`

```php
public function steamGames(): BelongsToMany   // → SteamGame via genre_steam_game
```

---

### Trait: `HasBacklogStatus`

Gedeeld trait voor backlog-scopes. Kan hergebruikt worden op andere gaming-modellen (PlayStation, Switch, etc.).

```php
// app/Traits/HasBacklogStatus.php
trait HasBacklogStatus
{
    public function scopeInBacklog(Builder $query): Builder
    public function scopeWantToPlay(Builder $query): Builder
    public function scopePlaying(Builder $query): Builder
    public function scopeCompleted(Builder $query): Builder
    public function scopeDropped(Builder $query): Builder
}
```

---

## 4. Enums

### `BacklogStatus`

```php
enum BacklogStatus: string
{
    case WantToPlay = 'want_to_play';
    case Playing    = 'playing';
    case Completed  = 'completed';
    case Dropped    = 'dropped';

    public function label(): string  // Nederlandse weergave, bijv. "Wil spelen"
    public function color(): string  // Hex kleurcode voor UI
    public function icon(): string   // Font Awesome icon class
}
```

### `PlayMode`

```php
enum PlayMode: string
{
    case Singleplayer = 'singleplayer';
    case Multiplayer  = 'multiplayer';
    case Both         = 'both';

    public function label(): string
    public function icon(): string
    public function color(): string
}
```

---

## 5. SteamApiService

**Bestand:** `app/Services/Steam/SteamApiService.php`

Constructor laadt `config('services.steam.api_key')` en `config('services.steam.steam_id')`.
Fluent setters `setApiKey()` en `setSteamId()` laten runtime-overschrijving toe.

### Steam API endpoints

| Endpoint | Gebruik |
|---|---|
| `/IPlayerService/GetOwnedGames/v1/` | Volledige bibliotheek met `include_appinfo=1` en `include_played_free_games=1` |
| `/IPlayerService/GetRecentlyPlayedGames/v1/` | Spellen gespeeld in de afgelopen 2 weken |
| `/ISteamUser/ResolveVanityURL/v1/` | Converteert profiel-URL naar Steam ID |

### Methoden

| Methode | Return | Doel |
|---|---|---|
| `getOwnedGames()` | `array` | Haalt volledige bibliotheek op |
| `getRecentlyPlayedGames()` | `array` | Haalt recente spellen op |
| `syncGames()` | `array` | Haalt bibliotheek op + `updateOrCreate` per spel |
| `testConnection()` | `array` | Valideert API key + Steam ID |
| `resolveVanityUrl(string $url)` | `array` | Converteert profiel-URL naar Steam ID |

### `syncGames()` flow

1. Roept `getOwnedGames()` aan
2. Mapt API-respons naar app-veldnamen
3. Per spel: `SteamGame::updateOrCreate(['steam_appid' => $appid], [...])`
4. Retourneert `['success' => true, 'game_count' => X]` of `['success' => false, 'error' => '...']`

Geen queue — alles synchroon in de controller.

---

## 6. Controllers & Routes

### Structuur

```
app/Http/Controllers/Gaming/
└── SteamController.php        // index, show, edit, update, sync, settings, testConnection
```

### Routes (`routes/gaming.php`)

```php
Route::prefix('steam')->group(function () {
    Route::get('/',                      [SteamController::class, 'index'])->name('steam.index');
    Route::get('/settings',              [SteamController::class, 'settings'])->name('steam.settings');
    Route::get('/games/{game}',          [SteamController::class, 'show'])->name('steam.games.show');
    Route::get('/games/{game}/edit',     [SteamController::class, 'edit'])->name('steam.games.edit');
    Route::put('/games/{game}',          [SteamController::class, 'update'])->name('steam.games.update');
    Route::post('/sync',                 [SteamController::class, 'sync'])->name('steam.sync');
    Route::post('/test-connection',      [SteamController::class, 'testConnection'])->name('steam.test-connection');
});
```

Alle routes zijn web routes. `testConnection` retourneert JSON voor AJAX-gebruik.

### Validatieregels (`SteamController::update`)

```php
'price'                => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
'play_mode'            => ['nullable', Rule::enum(PlayMode::class)],
'main_story_completed' => ['nullable', 'boolean'],
'user_rating'          => ['nullable', 'integer', 'min:1', 'max:10'],
'critic_rating'        => ['nullable', 'integer', 'min:1', 'max:100'],
```

### Backlog-status updates

Backlog-status wijzigingen lopen via een aparte `BacklogController`:

```
PATCH /gaming/backlog/{type}/{id}/status
```

Ondersteunt meerdere platforms door `{type}` (bijv. `steam`, `playstation`). Gebruikt `HasBacklogStatus` trait scopes.

---

## 7. Views

### `steam/index.blade.php` — Overzicht

**Header**
- Paginatitel + breadcrumb
- "Aanbeveling" knop
- "Sync from Steam" knop → POST `/gaming/steam/sync`

**Statistieken (4 kaarten)**
- Totaal spellen (count)
- Totaal besteed (som van `price`)
- Totale uren (`playtime_minutes / 60`)
- Recente uren (`playtime_2weeks_minutes / 60`, afgelopen 2 weken)

**Meest gespeeld (top 5)**
- Per spel: game icon, naam, geformatteerde speeltijd
- Gesorteerd op `playtime_minutes DESC`

**Recent gespeeld (top 5)**
- Gesorteerd op `last_played_at DESC`

**Filter & zoeken**
- Tekst zoeken op spelnaam
- Sorteren op: speeltijd (standaard), speeltijd 2 weken, last played, naam
- Filteren op backlog status
- "Filters wissen" knop

**Spellentabel** (gepagineerd, 25 per pagina)
- Game icon
- Spelnaam (klikbaar → `steam.games.show`)
- Backlog status badge
- Speeltijd (uren + minuten)
- Laatste speeldatum
- Bewerklink

---

### `steam/show.blade.php` — Speldetails

**Header**
- Speltitel + breadcrumb
- Backlog status selector (dropdown om status te wijzigen)
- "View on Steam" knop (externe link via `steam_url` accessor)
- Bewerkknop + terugknop

**Spelinfo**
- Game image (120×120)
- Naam
- Info-blokken:
  - Totale speeltijd (uren)
  - Recente speeltijd (afgelopen 2 weken)
  - Laatste speeldatum/-tijd
  - Betaalde prijs (indien ingevuld)

**Statistieken**
- Totaal uren gespeeld
- Totaal minuten gespeeld
- Cost per hour (berekend: `price / playtime_hours`) met waardeoordeel: Excellent / Good / OK / Poor
- Cost per minute
- Play mode (singleplayer/multiplayer/beide) met icoon
- Main story completed status

**Metadata**
- Steam App ID
- Steam URL (klikbare link)

---

### `steam/edit.blade.php` — Bewerken

**Spelweergave (read-only)**
- Game image + naam
- Huidige speeltijd

**Bewerkbare velden**
- Prijs (decimaal, optioneel)
- User rating (1–10, optioneel)
- Critic rating (1–100, optioneel)
- Play mode selector component
- Main story completed checkbox
- Genre multi-selector component

**Acties**
- "Opslaan" knop
- "Annuleren" knop

---

### `steam/settings.blade.php` — Configuratie

**Configuratiesectie**
- Instructies voor `.env` setup
- Vereiste variabelen in code-blok
- Statustabel:
  - API Key: Geconfigureerd / Niet geconfigureerd
  - Steam ID: waarde of Niet geconfigureerd
- "Test Connection" knop (AJAX → `steam.test-connection`)
- Resultaatweergave

**Instructies**
- Hoe Steam API key ophalen (link naar Steam dev page)
- Hoe Steam ID vinden (genummerde stappen)

---

## 8. Omgevingsvariabelen

Toevoegen aan `.env` én `.env.example`:

```env
STEAM_API_KEY=
STEAM_ID=
```

| Variabele | Verplicht | Beschrijving |
|---|---|---|
| `STEAM_API_KEY` | ja | API key via https://steamcommunity.com/dev/apikey |
| `STEAM_ID` | ja | Steam ID (64-bit getal, of ophalen via vanity URL resolver) |

---

## 9. Implementatievolgorde

```
Fase 1 — Database & Models
 1.  php artisan make:model Genre -mf
 2.  php artisan make:model SteamGame -mf
 3.  Migraties schrijven: steam_games, genres, genre_steam_game
 4.  app/Traits/HasBacklogStatus.php aanmaken
 5.  Models invullen: casts, relaties, scopes, accessors, trait gebruiken

Fase 2 — Enums
 6.  php artisan make:enum BacklogStatus
 7.  php artisan make:enum PlayMode

Fase 3 — Steam API Service
 8.  app/Services/Steam/SteamApiService.php aanmaken
 9.  config/services.php uitbreiden met steam-sectie

Fase 4 — Controllers & Routes
10.  php artisan make:controller Gaming/SteamController
11.  php artisan make:controller Gaming/BacklogController
12.  routes/gaming.php aanmaken met alle Steam + backlog routes
13.  gaming.php importeren in bootstrap/app.php

Fase 5 — Views
14.  resources/views/steam/index.blade.php (overzicht + statistieken + filters)
15.  resources/views/steam/show.blade.php (speldetails + cost-per-hour)
16.  resources/views/steam/edit.blade.php (bewerkformulier)
17.  resources/views/steam/settings.blade.php (configuratie + connection test)

Fase 6 — Afwerking
18.  php artisan make:test Gaming/SteamTest --pest
19.  vendor/bin/pint --dirty
```

# Feature: Muziek

Luistergeschiedenis automatisch ophalen via Spotify's recently-played endpoint.
Tracks, albums en artiesten worden aangemaakt bij sync. Het dashboard toont recente plays en statistieken.

---

## Inhoudsopgave

1. [Database schema](#1-database-schema)
2. [OAuth flow & token beheer](#2-oauth-flow--token-beheer)
3. [Spotify Services](#3-spotify-services)
4. [Sync pipeline](#4-sync-pipeline)
5. [Artisan command & Scheduler](#5-artisan-command--scheduler)
6. [Middleware](#6-middleware)
7. [Models](#7-models)
8. [Controllers & Routes](#8-controllers--routes)
9. [Views — Dashboard](#9-views--dashboard)
10. [Views — Detail pagina's](#10-views--detail-paginas)
11. [Statistieken](#11-statistieken)
12. [Omgevingsvariabelen & Scopes](#12-omgevingsvariabelen--scopes)
13. [Implementatievolgorde](#13-implementatievolgorde)

---

## 1. Database schema

### `settings`

Algemene sleutel-waarde tabel voor applicatie-instellingen en credentials.

| Kolom        | Type    | Eigenschappen |
|--------------|---------|---------------|
| `id`         | bigint  | primary key   |
| `key`        | string  | unique        |
| `value`      | json    | nullable      |
| `created_at` | timestamp |             |
| `updated_at` | timestamp |             |

Spotify credentials worden hier opgeslagen onder de sleutels:
`spotify_id`, `spotify_access_token`, `spotify_refresh_token`, `spotify_token_expires_at`

---

### `artists`

| Kolom             | Type             | Eigenschappen    |
|-------------------|------------------|------------------|
| `id`              | bigint           | primary key      |
| `spotify_artist_id` | string         | unique, nullable |
| `name`            | string           | not null         |
| `image_url`       | string           | nullable         |
| `genres`          | json             | nullable         |
| `popularity`      | unsigned tinyint | nullable (0–100) |
| `created_at`      | timestamp        |                  |
| `updated_at`      | timestamp        |                  |

---

### `albums`

| Kolom             | Type              | Eigenschappen    |
|-------------------|-------------------|------------------|
| `id`              | bigint            | primary key      |
| `spotify_album_id`| string            | unique, nullable |
| `name`            | string            | not null         |
| `image_url`       | string            | nullable         |
| `release_date`    | date              | nullable         |
| `album_type`      | string            | nullable (album/single/compilation) |
| `total_tracks`    | unsigned smallint | nullable         |
| `created_at`      | timestamp         |                  |
| `updated_at`      | timestamp         |                  |

---

### `tracks`

| Kolom             | Type              | Eigenschappen                  |
|-------------------|-------------------|--------------------------------|
| `id`              | bigint            | primary key                    |
| `spotify_track_id`| string            | unique, not null               |
| `album_id`        | bigint            | FK → albums (cascadeOnDelete)  |
| `title`           | string            | not null                       |
| `duration_ms`     | unsigned integer  | nullable                       |
| `popularity`      | unsigned tinyint  | nullable                       |
| `preview_url`     | string            | nullable                       |
| `spotify_uri`     | string            | nullable                       |
| `is_explicit`     | boolean           | default false                  |
| `created_at`      | timestamp         |                                |
| `updated_at`      | timestamp         |                                |

---

### `track_artists` (pivot)

| Kolom       | Type              | Eigenschappen                   |
|-------------|-------------------|---------------------------------|
| `id`        | bigint            | primary key                     |
| `track_id`  | bigint            | FK → tracks (cascadeOnDelete)   |
| `artist_id` | bigint            | FK → artists (cascadeOnDelete)  |
| `is_primary`| boolean           | default false                   |
| `sort_order`| unsigned tinyint  | default 0                       |
| `created_at`| timestamp         |                                 |
| `updated_at`| timestamp         |                                 |

Unique: `[track_id, artist_id]`

---

### `plays`

| Kolom        | Type      | Eigenschappen                  |
|--------------|-----------|--------------------------------|
| `id`         | bigint    | primary key                    |
| `track_id`   | bigint    | FK → tracks (cascadeOnDelete)  |
| `played_at`  | timestamp | not null                       |
| `source`     | string    | default 'spotify'              |
| `context`    | json      | nullable (playlist/album URI)  |
| `created_at` | timestamp |                                |
| `updated_at` | timestamp |                                |

Unique: `[track_id, played_at]` — voorkomt duplicaten bij herhaalde syncs.
Index: `played_at`

---

### `spotify_sync_cursors`

| Kolom            | Type             | Eigenschappen |
|------------------|------------------|---------------|
| `id`             | bigint           | primary key   |
| `last_played_at` | timestamp        | not null      |
| `synced_at`      | timestamp        | not null      |
| `plays_imported` | unsigned integer | default 0     |
| `created_at`     | timestamp        |               |
| `updated_at`     | timestamp        |               |

Slaat het tijdstip op van de meest recente geïmporteerde play. Wordt gebruikt als `after`-cursor bij de volgende sync.

---

## 2. OAuth flow & token beheer

### Verbinden

```
Gebruiker → GET /spotify/auth
         → SpotifyAuthController::redirect()
         → SpotifyAuthService::redirect()
             SpotifySession->getAuthorizeUrl(['scope' => config('spotify.default_scopes')])
             Sla state op in sessie voor CSRF-bescherming
         → Redirect naar accounts.spotify.com/authorize

Spotify  → GET /spotify/auth/callback?code=...
         → SpotifyAuthController::callback()
         → SpotifyAuthService::handleCallback($code)
             SpotifySession->requestAccessToken($code)
             SpotifyWebAPI->me() voor spotify_id van de gebruiker
         → Setting::storeSpotifyCredentials([
               spotify_id, access_token, refresh_token, expires_at
           ])
         → Redirect naar home met success-melding
```

### Token opslag

Tokens worden opgeslagen in de `settings`-tabel via `Setting::set()`:

| Sleutel | Waarde |
|---|---|
| `spotify_id` | Spotify gebruikers-ID |
| `spotify_access_token` | Access token (verloopt na ~1 uur) |
| `spotify_refresh_token` | Refresh token (langlevend) |
| `spotify_token_expires_at` | Unix timestamp van verloop |

### Automatische token refresh

`SpotifyService::getAuthenticatedApi()` wordt aangeroepen vóór elke Spotify-call:

```
1. Haal credentials op via Setting::getSpotifyCredentials()
2. Als access/refresh token leeg → return null (niet verbonden)
3. Als expires_at binnen 5 minuten verloopt:
      SpotifyAuthService::refreshAccessToken($refreshToken)
      Sla nieuw access_token, refresh_token en expires_at op in settings
      Bij invalid_grant: verwijder alle tokens (herverbinding vereist)
4. Return SpotifyWebAPI instantie met geldig access_token
```

### Verbreken

```php
SpotifyService::disconnect()
// Verwijdert via Setting::remove():
// spotify_id, spotify_access_token, spotify_refresh_token, spotify_token_expires_at
```

---

## 3. Spotify Services

### `SpotifyAuthService`

```php
public function redirect(): RedirectResponse
    // Genereert authorize URL met scopes + state, redirect naar Spotify

public function handleCallback(string $code): array
    // requestAccessToken($code), haalt access/refresh token + spotify user op
    // Geeft: access_token, refresh_token, expires_at, spotify_user

public function refreshAccessToken(string $refreshToken): array
    // Refresht via SpotifySession, geeft nieuwe tokens terug
    // Gooit Exception bij mislukken

public function isTokenExpired(int $expiresAt): bool
    // true als huidige tijd >= expires_at - 300 seconden

public function getSpotifyApi(string $accessToken): SpotifyWebAPI
    // Geeft geconfigureerde SpotifyWebAPI instantie terug
```

---

### `SpotifyService`

```php
public function getAuthenticatedApi(): ?SpotifyWebAPI
    // Combineert token-check, auto-refresh en API-instantie
    // Geeft null terug als Spotify niet verbonden of refresh mislukt

public function isConnected(): bool
    // true als refresh_token aanwezig is in settings

public function disconnect(): void
    // Verwijdert alle Spotify credentials uit settings
```

---

### `SpotifyTrackService`

```php
public function syncRecentlyPlayed(): array
    // Haalt 50 meest recente plays op via cursor, verwerkt elk item
    // Geeft: ['synced' => int, 'skipped' => int, 'total' => int]

public function getCurrentlyPlaying(): ?object
    // Haalt huidig afspelende track op
    // Geeft object met track_name, artist_names, album_name, album_image_url,
    //   is_playing, progress_ms, duration_ms, spotify_uri
    // Geeft null terug als niets speelt of Spotify niet verbonden
```

---

### `config/spotify.php`

```php
return [
    'client_id'      => env('SPOTIFY_CLIENT_ID'),
    'client_secret'  => env('SPOTIFY_CLIENT_SECRET'),
    'redirect_uri'   => env('SPOTIFY_REDIRECT_URI'),

    'default_scopes' => [
        'user-read-email',
        'user-read-private',
        'user-read-recently-played',
        'user-read-currently-playing',
        'user-read-playback-state',
    ],
];
```

---

## 4. Sync pipeline

### Spotify endpoint

```
GET /v1/me/player/recently-played?limit=50&after={unix_ms}
```

- Geeft maximaal 50 tracks, gesorteerd op `played_at DESC`
- `after` = Unix timestamp in milliseconden van de laatste bekende play
- Eerste sync: geen `after` → de 50 meest recente plays

### Verwerking per item

```
1. SpotifyService::getAuthenticatedApi() → SpotifyWebAPI of null
2. SpotifySyncCursor::lastPlayedAt() → Carbon of null (cursor voor after-param)
3. api->getMyRecentTracks(['limit' => 50, 'after' => cursor_in_ms])
4. Per item (track-object + played_at):

   a. Artist: firstOrCreate op spotify_artist_id
      → indien nieuw: haal details op via api->getArtist()
        voor image_url, genres en popularity

   b. Album: firstOrCreate op spotify_album_id
      → indien nieuw: haal details op via api->getAlbum()
        voor image_url, release_date, album_type en total_tracks

   c. Track: firstOrCreate op spotify_track_id
      met: title, album_id, duration_ms, popularity, preview_url, spotify_uri

   d. track_artists pivot: syncWithoutDetaching voor elke artiest van de track
      met: is_primary (index === 0), sort_order (index)

   e. Play: insertOrIgnore op [track_id, played_at]
      met: source = 'spotify', context = {type, uri} indien aanwezig

5. Sla cursor op: SpotifySyncCursor::update(hoogste played_at, aantal nieuwe plays)
```

---

## 5. Artisan command & Scheduler

### Command

```
php artisan spotify:sync-tracks
```

```php
// app/Console/Commands/SyncSpotifyTracks.php

public function handle(SpotifyTrackService $trackService): int
{
    $result = $trackService->syncRecentlyPlayed();

    $this->info("Sync completed!");
    $this->info("  - Tracks synced: {$result['synced']}");
    $this->info("  - Duplicates skipped: {$result['skipped']}");
    $this->info("  - Total processed: {$result['total']}");

    return Command::SUCCESS;
}
```

### Scheduler

```php
// routes/console.php
Schedule::command('spotify:sync-tracks')->everyFifteenMinutes();
```

> Spotify's recently-played endpoint heeft geen webhook — polling is de enige optie.
> De unique constraint op `[track_id, played_at]` voorkomt duplicaten bij herhaalde syncs.

---

## 6. Middleware

### `EnsureSpotifyIsConnected`

```php
public function handle(Request $request, Closure $next): Response
{
    if (! $this->spotifyService->isConnected()) {
        return redirect()->route('spotify.auth')
            ->with('warning', 'Please connect your Spotify account first.');
    }

    return $next($request);
}
```

Registreren als `spotify.connected` in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'spotify.connected' => EnsureSpotifyIsConnected::class,
    ]);
})
```

---

## 7. Models

### `Setting`

```php
public static function get(string $key, mixed $default = null): mixed
    // Cache::remember met TTL van 3600 seconden

public static function set(string $key, mixed $value): void
    // updateOrCreate + Cache::forget

public static function remove(string $key): void
    // delete + Cache::forget

public static function getSpotifyCredentials(): array
    // Geeft: spotify_id, access_token, refresh_token, expires_at

public static function storeSpotifyCredentials(array $credentials): void
    // Slaat spotify_id, access_token, refresh_token, expires_at op
```

---

### `Artist`

```php
protected $fillable = ['name', 'spotify_artist_id', 'image_url', 'genres', 'popularity'];

protected function casts(): array
{
    return ['genres' => 'array'];
}

public function tracks(): BelongsToMany   // via track_artists, met is_primary + sort_order
```

---

### `Album`

```php
protected $fillable = ['name', 'spotify_album_id', 'image_url', 'release_date', 'album_type', 'total_tracks'];

protected function casts(): array
{
    return ['release_date' => 'date'];
}

public function tracks(): HasMany

public function releaseYear(): Attribute  // int: jaar uit release_date
```

---

### `Track`

```php
protected $fillable = ['title', 'spotify_track_id', 'album_id', 'duration_ms',
                        'popularity', 'preview_url', 'spotify_uri', 'is_explicit'];

public function album(): BelongsTo
public function artists(): BelongsToMany      // via track_artists, orderBy sort_order
public function plays(): HasMany

public function getPrimaryArtistAttribute(): ?Artist
    // artists->firstWhere('pivot.is_primary', true) ?? artists->first()

public function getArtistsStringAttribute(): string
    // artists->pluck('name')->implode(', ')

public function getFormattedDurationAttribute(): string
    // "3:45" op basis van duration_ms
```

---

### `Play`

```php
protected $fillable = ['track_id', 'played_at', 'source', 'context'];

protected function casts(): array
{
    return [
        'played_at' => 'datetime',
        'context'   => 'array',
    ];
}

public function track(): BelongsTo
```

---

### `SpotifySyncCursor`

```php
protected function casts(): array
{
    return [
        'last_played_at' => 'datetime',
        'synced_at'      => 'datetime',
    ];
}

public static function lastPlayedAt(): ?Carbon
    // Laatste record ophalen, geeft last_played_at terug of null

public static function update(Carbon $playedAt, int $playsImported): self
    // updateOrCreate met synced_at = now()
```

---

## 8. Controllers & Routes

### Auth routes

```php
// routes/web.php of routes/spotify.php
Route::get('/spotify/auth',          [SpotifyAuthController::class, 'redirect'])->name('spotify.auth');
Route::get('/spotify/auth/callback', [SpotifyAuthController::class, 'callback'])->name('spotify.callback');
```

### Music routes

```php
// routes/music.php
Route::prefix('music')->name('music.')->middleware('spotify.connected')->group(function () {
    Route::get('/',                 [MusicDashboardController::class, 'index'])->name('index');
    Route::get('/stats',            [MusicStatsController::class, 'index'])->name('stats');
    Route::get('/tracks/{track}',   [TrackController::class, 'show'])->name('tracks.show');
    Route::get('/albums/{album}',   [AlbumController::class, 'show'])->name('albums.show');
    Route::get('/artists/{artist}', [ArtistController::class, 'show'])->name('artists.show');
});
```

### Controller structuur

```
app/Http/Controllers/
├── Spotify/
│   └── SpotifyAuthController.php     // redirect, callback
└── Music/
    ├── MusicDashboardController.php  // index
    ├── MusicStatsController.php      // index
    ├── TrackController.php           // show
    ├── AlbumController.php           // show
    └── ArtistController.php          // show
```

---

## 9. Views — Dashboard

### `pages/music/index.blade.php`

**Header**
- Titel "Music"
- "Statistics" knop (secundair)
- Laatste sync-tijdstip: "Last synced X minutes ago" (op basis van `SpotifySyncCursor`)

**Nu aan het luisteren** (conditioneel, via `SpotifyTrackService::getCurrentlyPlaying()`)
- Albumhoes + tracknaam + artiestennaam + voortgangsbalk

**Recente plays** (laatste 20)
- Per play:
  - Albumhoes (klein, vierkant)
  - Tracknaam (klikbaar → `music.tracks.show`)
  - Artiestennaam (klikbaar → `music.artists.show`)
  - Albumtitel (klikbaar → `music.albums.show`)
  - `played_at` relatief ("Vandaag om 14:32" / "Gisteren om 23:11")

**Top tracks deze week** (top 5)
- Tracknaam + artiestennaam + play count badge

**Top artiesten deze week** (top 5)
- Artiestenfoto (rond, klein) + naam + play count

---

## 10. Views — Detail pagina's

### `pages/music/tracks/show.blade.php`

- Albumhoes + tracknaam, artiestennaam (klikbaar), albumtitel (klikbaar)
- Duur, explicit-badge
- Totale play count + eerste/laatste keer geluisterd
- Luisterhistorie: lijst van alle `played_at` timestamps

### `pages/music/albums/show.blade.php`

- Albumhoes + naam, artiestennaam (klikbaar), releasejaar, album type, aantal tracks
- Totale play count (som van plays van alle tracks in dit album)
- Tracklist: genummerd, met play count per track
- Eerste/laatste keer geluisterd

### `pages/music/artists/show.blade.php`

- Artiestenfoto + naam, genres
- Totale plays, unieke tracks gehoord
- Top tracks van deze artiest (op play count)
- Albums van deze artiest in collectie (grid met albumhoezen)

---

## 11. Statistieken

### `pages/music/stats.blade.php`

**Hero stats**
- Totaal plays ooit + totale luistertijd in uren

**Grid — 4 overzichtscijfers**
- Unieke tracks gehoord
- Unieke artiesten
- Unieke albums
- Gemiddeld plays per dag

**First & Last Play** (2 kolommen)
- Albumhoes + tracknaam + artiestennaam + datum

**Plays per dag** (Chart.js bar chart)
- Periodefilter: 7 / 30 / 90 / 180 / 365 dagen

**Activity heatmap** (laatste 365 dagen)
- GitHub-stijl contribution graph
- 5 kleursniveaus op basis van plays per dag
- Tooltip: datum + aantal plays
- Maandlabels + legende

**Top tracks** (top 10, instelbare periode)
- Albumhoes + tracknaam + artiestennaam + play count

**Top artiesten** (top 10, instelbare periode)
- Artiestenfoto + naam + play count + unieke tracks

**Top albums** (top 10, instelbare periode)
- Albumhoes + naam + artiestennaam + play count

**Luisterpatronen**
- Meest actieve uur van de dag (bar chart, 0–23u)
- Meest actieve weekdag

**Recente ontdekkingen**
- Tracks die voor het eerst gehoord zijn in de afgelopen 30 dagen

---

## 12. Omgevingsvariabelen & Scopes

Toevoegen aan `.env` én `.env.example`:

```env
SPOTIFY_CLIENT_ID=
SPOTIFY_CLIENT_SECRET=
SPOTIFY_REDIRECT_URI=
```

| Variabele | Verplicht | Beschrijving |
|---|---|---|
| `SPOTIFY_CLIENT_ID` | ja | Spotify Developer App client ID |
| `SPOTIFY_CLIENT_SECRET` | ja | Spotify Developer App client secret |
| `SPOTIFY_REDIRECT_URI` | ja | OAuth callback URL (bijv. `http://localhost/spotify/auth/callback`) |

Vereiste scopes (gedefinieerd in `SpotifyScope` enum + `config/spotify.php`):

| Scope | Waarde | Gebruik |
|---|---|---|
| `USER_READ_EMAIL` | `user-read-email` | Spotify gebruikersprofiel |
| `USER_READ_PRIVATE` | `user-read-private` | Spotify gebruikersprofiel |
| `USER_READ_RECENTLY_PLAYED` | `user-read-recently-played` | Sync pipeline |
| `USER_READ_CURRENTLY_PLAYING` | `user-read-currently-playing` | Nu aan het luisteren |
| `USER_READ_PLAYBACK_STATE` | `user-read-playback-state` | Nu aan het luisteren |

---

## 13. Implementatievolgorde

```
Fase 1 — Database & Models
 1.  php artisan make:model Setting -m
 2.  php artisan make:model Artist -mf
 3.  php artisan make:model Album -mf
 4.  php artisan make:model Track -mf
 5.  php artisan make:model Play -mf
 6.  php artisan make:model SpotifySyncCursor -m
 7.  Migraties schrijven (alle tabellen incl. track_artists pivot)
 8.  Models invullen: fillable, casts, relaties, accessors

Fase 2 — Spotify integratie
 9.  SpotifyScope enum aanmaken
10.  config/spotify.php aanmaken
11.  SpotifyAuthService aanmaken
12.  SpotifyService aanmaken
13.  SpotifyTrackService aanmaken

Fase 3 — Auth & Middleware
14.  php artisan make:controller Spotify/SpotifyAuthController
15.  php artisan make:middleware EnsureSpotifyIsConnected
16.  Middleware registreren als alias 'spotify.connected' in bootstrap/app.php
17.  Auth routes toevoegen

Fase 4 — Sync
18.  php artisan make:command SyncSpotifyTracks
19.  Scheduler instellen in routes/console.php

Fase 5 — Controllers & Routes
20.  Music controllers aanmaken (Dashboard, Stats, Track, Album, Artist)
21.  routes/music.php aanmaken + importeren in bootstrap/app.php

Fase 6 — Views
22.  pages/music/index.blade.php (dashboard)
23.  pages/music/tracks/show.blade.php
24.  pages/music/albums/show.blade.php
25.  pages/music/artists/show.blade.php
26.  pages/music/stats.blade.php

Fase 7 — Afwerking
27.  SCSS: resources/scss/features/_music.scss aanmaken + importeren
28.  php artisan make:test Music/SpotifySyncTest --pest
29.  php artisan make:test Music/MusicDashboardTest --pest
30.  vendor/bin/pint --dirty
```

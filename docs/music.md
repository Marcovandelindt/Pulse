# Feature: Muziek

Albums bijhouden via Spotify-integratie. Zoeken, toevoegen, luisterhistorie bijhouden,
nummers per album bekijken, artiestenpagina's bekijken en uitgebreide statistieken inzien.

---

## Inhoudsopgave

1. [Database schema](#1-database-schema)
2. [Listen date — de drie scenario's](#2-listen-date--de-drie-scenarios)
3. [Models](#3-models)
4. [Spotify Services](#4-spotify-services)
5. [Actions](#5-actions)
6. [Controllers & Routes](#6-controllers--routes)
7. [Views — Albums](#7-views--albums)
8. [Views — Artiesten](#8-views--artiesten)
9. [Statistieken](#9-statistieken)
10. [Gedeelde UI-patronen](#10-gedeelde-ui-patronen)
11. [Frontend dependencies](#11-frontend-dependencies)
12. [Omgevingsvariabelen](#12-omgevingsvariabelen)
13. [Implementatievolgorde](#13-implementatievolgorde)

---

## 1. Database schema

### `artists`

| Kolom          | Type             | Eigenschappen    |
|----------------|------------------|------------------|
| `id`           | bigint           | primary key      |
| `spotify_id`   | string           | unique, not null |
| `name`         | string           | not null         |
| `image_path`   | string           | nullable         |
| `genres`       | json             | nullable         |
| `popularity`   | unsigned tinyint | nullable (0–100) |
| `created_at`   | timestamp        |                  |
| `updated_at`   | timestamp        |                  |

Index: `spotify_id` (unique)

---

### `albums`

| Kolom               | Type              | Eigenschappen                      |
|---------------------|-------------------|------------------------------------|
| `id`                | bigint            | primary key                        |
| `spotify_id`        | string            | unique, not null                   |
| `artist_id`         | bigint            | FK → artists (cascadeOnDelete)     |
| `name`              | string            | not null                           |
| `image_path`        | string            | nullable                           |
| `release_date`      | date              | nullable                           |
| `release_year`      | unsigned smallint | nullable                           |
| `track_count`       | unsigned smallint | default 0                          |
| `duration_ms`       | unsigned integer  | nullable (totale duur)             |
| `genres`            | json              | nullable                           |
| `album_type`        | string            | nullable (album/single/ep)         |
| `label`             | string            | nullable                           |
| `listen_count`      | unsigned integer  | default 0                          |
| `last_listened_at`  | timestamp         | nullable                           |
| `first_listened_at` | timestamp         | nullable                           |
| `created_at`        | timestamp         |                                    |
| `updated_at`        | timestamp         |                                    |

Indexes: `spotify_id` (unique), `artist_id`, `last_listened_at`

---

### `tracks`

| Kolom          | Type              | Eigenschappen                   |
|----------------|-------------------|---------------------------------|
| `id`           | bigint            | primary key                     |
| `spotify_id`   | string            | unique, not null                |
| `album_id`     | bigint            | FK → albums (cascadeOnDelete)   |
| `name`         | string            | not null                        |
| `track_number` | unsigned smallint | not null                        |
| `disc_number`  | unsigned tinyint  | default 1                       |
| `duration_ms`  | unsigned integer  | nullable                        |
| `preview_url`  | string            | nullable                        |
| `is_explicit`  | boolean           | default false                   |
| `created_at`   | timestamp         |                                 |
| `updated_at`   | timestamp         |                                 |

Unique: `[album_id, track_number, disc_number]` — Index: `album_id`, `spotify_id`

> Tracks worden alleen informatief opgeslagen — luisterTracking gebeurt op albumniveau.

---

### `album_listens`

| Kolom         | Type      | Eigenschappen                    |
|---------------|-----------|----------------------------------|
| `id`          | bigint    | primary key                      |
| `album_id`    | bigint    | FK → albums (cascadeOnDelete)    |
| `listened_at` | timestamp | nullable                         |
| `year_only`   | boolean   | default false                    |
| `notes`       | text      | nullable                         |
| `rating`      | tinyint   | nullable (1–10)                  |
| `created_at`  | timestamp |                                  |
| `updated_at`  | timestamp |                                  |

Indexes: `album_id`, `listened_at`

---

### `album_artist` (pivot — voor featuring / meerdere artiesten)

| Kolom        | Type      | Eigenschappen                   |
|--------------|-----------|---------------------------------|
| `id`         | bigint    | primary key                     |
| `album_id`   | bigint    | FK → albums (cascadeOnDelete)   |
| `artist_id`  | bigint    | FK → artists (cascadeOnDelete)  |
| `is_primary` | boolean   | default false                   |
| `created_at` | timestamp |                                 |
| `updated_at` | timestamp |                                 |

Unique: `[album_id, artist_id]`

---

### Cascade-deletes

```
Artist  → albums              (cascade)
            → tracks          (cascade)
            → album_listens   (cascade)
        → album_artist        (cascade)
Album   → tracks              (cascade)
        → album_listens       (cascade)
        → album_artist        (cascade)
```

---

## 2. Listen date — de drie scenario's

Elke luisterbeurt kent drie mogelijke datumstaten (identiek aan media):

| Scenario | `listened_at` | `year_only` | Weergave |
|---|---|---|---|
| Exacte datum bekend | `2024-03-15 00:00:00` | `false` | "15 maart 2024" |
| Alleen jaar bekend | `2019-01-01 00:00:00` | `true` | "2019" |
| Geen datum (gewoon geluisterd) | `null` | `false` | "Datum onbekend" |

### Opslaan

- **Exacte datum**: gebruiker kiest datum → `listened_at = date`, `year_only = false`
- **Alleen jaar**: gebruiker vult jaar in → `listened_at = {jaar}-01-01`, `year_only = true`
- **Geen datum**: leeg laten → `listened_at = null`, `year_only = false`

### Weergeven

```php
public function formattedListenedAt(): string
{
    if ($this->listened_at === null) {
        return 'Datum onbekend';
    }

    if ($this->year_only) {
        return $this->listened_at->format('Y');
    }

    return $this->listened_at->format('d M Y');
}
```

### UI — modal (radio buttons)

```
[●] Exacte datum   → toont datepicker (standaard geselecteerd, vandaag ingevuld)
[○] Alleen jaar    → toont jaar-input (4 cijfers, min 1900 max 2100)
[○] Geen datum     → datum verborgen
```

### Sortering

Luisterhistorie gesorteerd op `listened_at DESC NULLS LAST`.

---

## 3. Models

### `Artist`

```php
protected function casts(): array
{
    return [
        'genres' => 'array',
    ];
}

public function albums(): HasMany              // → Album, orderBy name ASC
public function featuredAlbums(): BelongsToMany  // → Album via album_artist

public function imageUrl(): Attribute
```

---

### `Album`

```php
protected function casts(): array
{
    return [
        'release_date'      => 'date',
        'genres'            => 'array',
        'last_listened_at'  => 'datetime',
        'first_listened_at' => 'datetime',
    ];
}

public function artist(): BelongsTo            // → Artist (primary artist)
public function artists(): BelongsToMany       // → Artist via album_artist
public function tracks(): HasMany              // → Track, orderBy disc_number, track_number ASC
public function listens(): HasMany             // → AlbumListen, orderBy listened_at DESC

public function imageUrl(): Attribute
public function formattedDuration(): Attribute // "1u 3m" op basis van duration_ms

public function incrementListenCount(): void
    // listen_count++, last_listened_at = now(), first_listened_at = now() als null
```

---

### `AlbumListen`

```php
protected function casts(): array
{
    return [
        'listened_at' => 'datetime',
        'year_only'   => 'boolean',
    ];
}

public function album(): BelongsTo
public function formattedListenedAt(): string  // zie sectie 2
```

---

### `Track`

```php
protected function casts(): array
{
    return [
        'is_explicit' => 'boolean',
    ];
}

public function album(): BelongsTo

public function formattedDuration(): Attribute // "3:45" op basis van duration_ms
```

---

## 4. Spotify Services

Bouwt voort op de bestaande `SpotifyAuthService` en de `jwilsson/spotify-web-api-php` package.
Alle Spotify-calls worden gecached. Cache-duur via `config('spotify.cache_duration')`.

### `SpotifyMusicService`

```php
public function search(string $query, int $limit = 20): array
    // Cache: "spotify_album_search_{md5(query)}_{limit}"
    // Type: album
    // Geeft: items[], total, limit, offset

public function getAlbum(string $spotifyId): array
    // Cache: "spotify_album_{spotifyId}"
    // Geeft: album details incl. tracks

public function getAlbumTracks(string $spotifyId): array
    // Cache: "spotify_album_tracks_{spotifyId}"
    // Pagineert automatisch als total > 50

public function getArtist(string $spotifyId): array
    // Cache: "spotify_artist_{spotifyId}"

public function createAlbumFromSpotify(string $spotifyId): Album
    // Velden: name, image_path, release_date, release_year, track_count,
    //         duration_ms, genres, album_type, label
    // Maakt of hergebruikt Artist via firstOrCreate op spotify_id
    // Synct tracks via syncTracks()
    // Koppelt featuring-artiesten via album_artist pivot

public function syncTracks(Album $album, array $tracksData): void
    // Maakt alle Track-records aan (upsert op spotify_id)

public function clearCache(string $spotifyId): void
    // Verwijdert: album, album_tracks cache
```

---

### `config/spotify.php` (uitbreiden)

```php
return [
    'client_id'      => env('SPOTIFY_CLIENT_ID'),
    'client_secret'  => env('SPOTIFY_CLIENT_SECRET'),
    'redirect_uri'   => env('SPOTIFY_REDIRECT_URI'),
    'cache_duration' => env('SPOTIFY_CACHE_DURATION', 1440),

    'image_sizes' => [
        'small'  => 64,
        'medium' => 300,
        'large'  => 640,
    ],
];
```

---

## 5. Actions

```
app/Actions/Music/
├── AddAlbumFromSpotify.php    // spotifyId → Album
├── LogAlbumListen.php         // Album + AlbumListenData → AlbumListen
├── DeleteAlbumListen.php      // AlbumListen → void (herbereken listen_count)
└── DeleteAlbum.php            // Album → void
```

### DTO

```php
final readonly class AlbumListenData
{
    public function __construct(
        public ?Carbon  $listenedAt,
        public bool     $yearOnly,
        public ?string  $notes,
        public ?int     $rating,
    ) {}

    public static function fromRequest(LogAlbumListenRequest $request): self
}
```

---

## 6. Controllers & Routes

### Structuur

```
app/Http/Controllers/Music/
├── AlbumController.php        // index, show, store, destroy
├── AlbumSearchController.php  // index → JSON (Spotify search)
├── AlbumListenController.php  // store, destroy
├── AlbumStatsController.php   // index
└── ArtistController.php       // show
```

### Validatieregels

```php
// LogAlbumListenRequest
'listened_at' => ['nullable', 'date', 'before_or_equal:now'],
'year_only'   => ['boolean'],
'notes'       => ['nullable', 'string', 'max:2000'],
'rating'      => ['nullable', 'integer', 'between:1,10'],
```

### Routes

```php
// routes/music.php
Route::prefix('music')->name('music.')->group(function () {
    Route::get('/',                    [AlbumController::class, 'index'])->name('index');
    Route::get('/stats',               [AlbumStatsController::class, 'index'])->name('stats');
    Route::get('/artists/{artist}',    [ArtistController::class, 'show'])->name('artists.show');
    Route::get('/{album}',             [AlbumController::class, 'show'])->name('show');
    Route::post('/search',             [AlbumSearchController::class, 'index'])->name('search');
    Route::post('/',                   [AlbumController::class, 'store'])->name('store');
    Route::post('/{album}/listens',    [AlbumListenController::class, 'store'])->name('listens.store');
    Route::delete('/listens/{listen}', [AlbumListenController::class, 'destroy'])->name('listens.destroy');
    Route::delete('/{album}',          [AlbumController::class, 'destroy'])->name('destroy');
});
```

---

## 7. Views — Albums

### `pages/music/index.blade.php`

**Header**
- Titel "Music"
- Knoppen: "Statistics" (secundair) + "Add Album" (primair)

**Lege state**
- Muziek-icoon, "No albums yet", "Add Your First Album" knop

**Met albums**
- Zoekbalk: real-time client-side filtering op `data-title` + `data-artist`
- Grid van albumkaarten, gesorteerd op `last_listened_at DESC, created_at DESC`
- Per kaart:
  - Albumhoes (vierkant, of placeholder icoon)
  - Albumtitel
  - Artiestennaam (klikbaar → `music.artists.show`)
  - Listen count, releasejaar
  - Klikbaar → `music.show`

**Add Album modal**
- Spotify-zoekbalk → live resultaten (albumhoes + naam + artiest + jaar)
- Klik op resultaat → POST `/music` met `spotify_id`
- Sluit automatisch na toevoegen, toast notification

---

### `pages/music/show.blade.php`

**Header**
- Albumtitel + breadcrumb (Home → Music → Titel)
- Knoppen: "Log Listen" (primair) + "Delete" (danger, met confirm-dialog)

**Album info**
- Albumhoes (links, vierkant) + info (rechts):
  - Albumtitel, artiestennaam (klikbaar), album type badge (Album / Single / EP)
  - Releasedatum, totale duur, aantal tracks
  - Genre-badges
  - Listen count, first listened, last listened

**Tracklist**
- Genummerde lijst van tracks (disc-header indien > 1 disc)
- Per track: nummer, naam, duur
- Expliciete tracks: klein "E"-badge

**Luisterhistorie**
- Gesorteerd op `listened_at DESC NULLS LAST`
- Per listen: datum (via `formattedListenedAt()`), beoordeling, notities, delete-knop
- Delete via `DELETE /music/listens/{listen}` + browser confirm-dialog

**Log Listen modal** (zie sectie 2 voor datumlogica)
- Radio: "Exacte datum" (standaard) / "Alleen jaar" / "Geen datum"
- Beoordeling (1–10), notities
- POST naar `/music/{album}/listens`
- Sluit automatisch + toast notification

---

## 8. Views — Artiesten

### `pages/music/artists/show.blade.php`

Bereikbaar via klik op artiestennaam op album-kaart of -detailpagina.

**Header**
- Artiestennaam + breadcrumb (Home → Music → Naam)

**Profiel-kaart**
- Artiestenfoto (150px, rond) + statistieken-grid:
  - Aantal albums in collectie
  - Totaal luisterbeurten
  - Gemiddelde beoordeling
  - Genre-badges
  - Eerste keer geluisterd (conditioneel)
  - Laatste keer geluisterd (conditioneel)

**Albums-sectie**
- Grid: `repeat(auto-fill, minmax(160px, 1fr))`
- Per album:
  - Albumhoes (vierkant) of placeholder
  - Albumtitel
  - Releasejaar + album type badge
  - Listen count badge — alleen als > 0
  - Klikbaar → `music.show`

**Controller: `ArtistController@show`**

```php
public function show(Artist $artist): View
{
    $albums = $artist->albums()->with('listens')->get();

    return view('pages.music.artists.show', [
        'artist'        => $artist,
        'albums'        => $albums,
        'totalListens'  => $albums->sum(fn ($a) => $a->listens->count()),
        'averageRating' => $albums->flatMap->listens->whereNotNull('rating')->avg('rating'),
        'firstListened' => $this->firstListened($albums),
        'lastListened'  => $this->lastListened($albums),
    ]);
}
```

---

## 9. Statistieken

### `pages/music/stats.blade.php`

**Hero stat**: totaal gelogde luisterbeurten + totale luistertijd in uren

**Grid — 4 overzichtscijfers**
- Totaal albums in collectie
- Unieke artiesten
- Gemiddelde beoordeling
- Meest actieve luistermaand

**First & Last Listen** (2 kolommen)
- Albumhoes + albumtitel + artiestennaam + datum
- Klikbaar → `music.show`

**Meest beluisterde albums** (top 10)
- Grid: albumhoes, titel, artiestennaam, listen count badge, rating, laatste luisterdatum

**Meest beluisterde artiesten** (top 10)
- Gerangschikte lijst: artiestennaam, totaal albums, totaal listens
- Klikbaar → `music.artists.show`

**Luisterbeurten per dag** (Chart.js bar chart)
- Periodefilter: 30 / 60 / 90 / 180 / 360 dagen + Custom
- Custom range: twee datuminputs
- Toont: totaal listens in periode + gemiddeld listens/dag

**Activity heatmap** (laatste 365 dagen)
- GitHub-stijl contribution graph
- 5 kleursniveaus op basis van listens per dag
- Tooltip per dag: datum + aantal luisterbeurten
- Maandlabels + legende (Less → More)

**Beoordeling-distributie**
- Bar chart: score 1–10 vs. aantal beoordelingen

**Genre-verdeling**
- Max 10 genres
- Per genre: naam + count (albums)

**Album type-verdeling**
- Album / Single / EP: aantallen + percentages

**Cumulatieve luisterbeurten**
- Line chart: totaal luisterbeurten over tijd (oplopend)

**Recente luisterhistorie**
- Max 10 dagen
- Per dag: datum + albumtitels + artiestennamen

**Kijkpatronen**
- Meest actieve weekdag (maandag t/m zondag)
- Langste streak (aaneengesloten luisterdagen)
- Gemiddeld albums/maand

**Persoonlijke records**
- Hoogst beoordeelde albums (grid met albumhoezen + rating)
- Meest herbeluisterde albums (titel, artiest, listen count)
- Eerste album per jaar (chronologisch overzicht)

---

## 10. Gedeelde UI-patronen

### Toast notifications
- Positie: fixed top-right
- Groen voor success, rood voor error
- Verdwijnt automatisch na 3 seconden
- Slide-in animatie (translateX 100% → 0)

### Modals
- Overlay met backdrop (klik sluit modal)
- Close-knop rechtsboven
- Enter-toets submit in formulier-modals

### Delete-bevestiging
- Browser `confirm()` dialog voor destructieve acties

### Lege states
Elke conditionele sectie toont een icoon + beschrijvende tekst als er geen data is.

### Client-side zoeken
Index-pagina filtert real-time op `data-*` attributen via een `oninput`-handler.

---

## 11. Frontend dependencies

| Dependency | Gebruik |
|---|---|
| **Chart.js** | Bar, line charts op stats-pagina |
| **Alpine.js** | Modal state, toggle-interacties |

Kleuren-constanten voor charts (consistent met media-module):
```js
const PINK   = '#fa709a';
const YELLOW = '#fee140';
const PURPLE = '#764ba2';
const BLUE   = '#4facfe';
const GRID   = 'rgba(255,255,255,0.06)';
const TICK   = 'rgba(255,255,255,0.5)';
```

---

## 12. Omgevingsvariabelen

Al aanwezig in `.env` vanuit de bestaande Spotify-integratie:

```env
SPOTIFY_CLIENT_ID=
SPOTIFY_CLIENT_SECRET=
SPOTIFY_REDIRECT_URI=
SPOTIFY_CACHE_DURATION=1440
```

| Variabele | Verplicht | Default | Beschrijving |
|---|---|---|---|
| `SPOTIFY_CLIENT_ID` | ja | — | Spotify Developer App client ID |
| `SPOTIFY_CLIENT_SECRET` | ja | — | Spotify Developer App client secret |
| `SPOTIFY_REDIRECT_URI` | ja | — | OAuth callback URL |
| `SPOTIFY_CACHE_DURATION` | nee | `1440` | Cache-duur in minuten (1440 = 24 uur) |

---

## 13. Implementatievolgorde

```
Fase 1 — Database & Models
 1.  php artisan make:model Artist -mf
 2.  php artisan make:model Album -mf
 3.  php artisan make:model Track -mf
 4.  php artisan make:model AlbumListen -mf
 5.  Migraties schrijven (alle tabellen + pivot album_artist)
 6.  Models invullen: casts, relaties, methoden, accessors

Fase 2 — Spotify integratie
 7.  config/spotify.php uitbreiden met cache_duration + image_sizes
 8.  SpotifyMusicService aanmaken (bouwt op bestaande SpotifyAuthService)

Fase 3 — Actions & Requests
 9.  php artisan make:request Music/LogAlbumListenRequest
10.  AlbumListenData DTO aanmaken
11.  AddAlbumFromSpotify Action aanmaken
12.  LogAlbumListen Action aanmaken
13.  DeleteAlbumListen Action aanmaken
14.  DeleteAlbum Action aanmaken

Fase 4 — Controllers & Routes
15.  php artisan make:controller Music/AlbumController
16.  php artisan make:controller Music/AlbumSearchController
17.  php artisan make:controller Music/AlbumListenController
18.  php artisan make:controller Music/AlbumStatsController
19.  php artisan make:controller Music/ArtistController
20.  routes/music.php aanmaken + importeren in bootstrap/app.php

Fase 5 — Views
21.  pages/music/index.blade.php (grid + Spotify search modal)
22.  pages/music/show.blade.php (albuminfo + tracklist + luisterhistorie + modal)
23.  pages/music/artists/show.blade.php (artiestenprofiel + albumgrid)
24.  pages/music/stats.blade.php (charts + statistieken)

Fase 6 — Afwerking
25.  SCSS: resources/scss/features/_music.scss aanmaken + importeren
26.  php artisan make:test Music/AlbumTest --pest
27.  php artisan make:test Music/ArtistTest --pest
28.  vendor/bin/pint --dirty
```

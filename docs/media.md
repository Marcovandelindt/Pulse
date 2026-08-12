# Feature: Media (Movies & TV Series)

Films en series bijhouden via TMDB-integratie. Zoeken, toevoegen, kijkhistorie bijhouden,
voortgang per seizoen/aflevering tracken, acteurspagina's bekijken en uitgebreide statistieken inzien.

---

## Inhoudsopgave

1. [Database schema](#1-database-schema)
2. [Watch date — de drie scenario's](#2-watch-date--de-drie-scenarios)
3. [Models](#3-models)
4. [TMDB Services](#4-tmdb-services)
5. [Actions](#5-actions)
6. [Controllers & Routes](#6-controllers--routes)
7. [Views — Movies](#7-views--movies)
8. [Views — TV Series](#8-views--tv-series)
9. [Views — People](#9-views--people)
10. [Statistieken — Movies](#10-statistieken--movies)
11. [Statistieken — TV Series](#11-statistieken--tv-series)
12. [Gedeelde UI-patronen](#12-gedeelde-ui-patronen)
13. [Frontend dependencies](#13-frontend-dependencies)
14. [Omgevingsvariabelen](#14-omgevingsvariabelen)
15. [Implementatievolgorde](#15-implementatievolgorde)

---

## 1. Database schema

### `movies`

| Kolom               | Type                | Eigenschappen            |
|---------------------|---------------------|--------------------------|
| `id`                | bigint              | primary key              |
| `tmdb_id`           | unsigned integer    | unique, not null         |
| `title`             | string              | not null                 |
| `original_title`    | string              | not null                 |
| `overview`          | text                | nullable                 |
| `poster_path`       | string              | nullable                 |
| `backdrop_path`     | string              | nullable                 |
| `release_date`      | date                | nullable                 |
| `runtime`           | unsigned smallint   | nullable (minuten)       |
| `vote_average`      | decimal(3,1)        | nullable                 |
| `genres`            | json                | nullable                 |
| `original_language` | string(10)          | nullable                 |
| `watch_count`       | unsigned integer    | default 0                |
| `last_watched_at`   | timestamp           | nullable                 |
| `first_watched_at`  | timestamp           | nullable                 |
| `created_at`        | timestamp           |                          |
| `updated_at`        | timestamp           |                          |

Indexes: `tmdb_id` (unique), `last_watched_at`

---

### `movie_watches`

| Kolom            | Type      | Eigenschappen                    |
|------------------|-----------|----------------------------------|
| `id`             | bigint    | primary key                      |
| `movie_id`       | bigint    | FK → movies (cascadeOnDelete)    |
| `watched_at`     | timestamp | nullable                         |
| `year_only`      | boolean   | default false                    |
| `notes`          | text      | nullable                         |
| `rating`         | tinyint   | nullable (1–10)                  |
| `created_at`     | timestamp |                                  |
| `updated_at`     | timestamp |                                  |

Indexes: `movie_id`, `watched_at`

---

### `tv_series`

| Kolom                   | Type             | Eigenschappen            |
|-------------------------|------------------|--------------------------|
| `id`                    | bigint           | primary key              |
| `tmdb_id`               | unsigned integer | unique, not null         |
| `name`                  | string           | not null                 |
| `name_en`               | string           | nullable                 |
| `original_name`         | string           | not null                 |
| `overview`              | text             | nullable                 |
| `poster_path`           | string           | nullable                 |
| `backdrop_path`         | string           | nullable                 |
| `first_air_date`        | date             | nullable                 |
| `last_air_date`         | date             | nullable                 |
| `vote_average`          | decimal(3,1)     | nullable                 |
| `genres`                | json             | nullable                 |
| `status`                | string           | nullable                 |
| `original_language`     | string(10)       | nullable                 |
| `number_of_seasons`     | unsigned integer | default 0                |
| `number_of_episodes`    | unsigned integer | default 0                |
| `episodes_watched`      | unsigned integer | default 0                |
| `completion_percentage` | decimal(5,2)     | default 0                |
| `last_watched_at`       | timestamp        | nullable                 |
| `first_watched_at`      | timestamp        | nullable                 |
| `created_at`            | timestamp        |                          |
| `updated_at`            | timestamp        |                          |

Indexes: `tmdb_id` (unique), `last_watched_at`

> `name_en` bevat de Engelse naam voor niet-Engelstalige series.
> `status` voorbeelden: "Returning Series", "Ended", "Canceled", "In Production".

---

### `tv_seasons`

| Kolom              | Type             | Eigenschappen                        |
|--------------------|------------------|--------------------------------------|
| `id`               | bigint           | primary key                          |
| `tv_series_id`     | bigint           | FK → tv_series (cascadeOnDelete)     |
| `tmdb_id`          | unsigned integer | unique                               |
| `name`             | string           | not null                             |
| `overview`         | text             | nullable                             |
| `poster_path`      | string           | nullable                             |
| `season_number`    | unsigned tinyint | not null                             |
| `air_date`         | date             | nullable                             |
| `episode_count`    | unsigned integer | default 0                            |
| `episodes_watched` | unsigned integer | default 0                            |
| `created_at`       | timestamp        |                                      |
| `updated_at`       | timestamp        |                                      |

Unique: `[tv_series_id, season_number]` — Index: `tv_series_id`, `tmdb_id`

---

### `tv_episodes`

| Kolom            | Type              | Eigenschappen                        |
|------------------|-------------------|--------------------------------------|
| `id`             | bigint            | primary key                          |
| `tv_season_id`   | bigint            | FK → tv_seasons (cascadeOnDelete)    |
| `tmdb_id`        | unsigned integer  | unique                               |
| `name`           | string            | not null                             |
| `overview`       | text              | nullable                             |
| `episode_number` | unsigned smallint | not null                             |
| `air_date`       | date              | nullable                             |
| `runtime`        | unsigned smallint | nullable (minuten)                   |
| `vote_average`   | decimal(3,1)      | nullable                             |
| `created_at`     | timestamp         |                                      |
| `updated_at`     | timestamp         |                                      |

Unique: `[tv_season_id, episode_number]` — Index: `tv_season_id`, `tmdb_id`

---

### `episode_watches`

| Kolom           | Type      | Eigenschappen                            |
|-----------------|-----------|------------------------------------------|
| `id`            | bigint    | primary key                              |
| `tv_episode_id` | bigint    | FK → tv_episodes (cascadeOnDelete)       |
| `watched_at`    | timestamp | nullable                                 |
| `year_only`     | boolean   | default false                            |
| `notes`         | text      | nullable                                 |
| `rating`        | tinyint   | nullable (1–10)                          |
| `created_at`    | timestamp |                                          |
| `updated_at`    | timestamp |                                          |

Indexes: `tv_episode_id`, `watched_at`

---

### `people`

| Kolom          | Type             | Eigenschappen   |
|----------------|------------------|-----------------|
| `id`           | bigint           | primary key     |
| `tmdb_id`      | unsigned integer | unique          |
| `name`         | string           | not null        |
| `profile_path` | string           | nullable        |
| `created_at`   | timestamp        |                 |
| `updated_at`   | timestamp        |                 |

---

### `movie_person` (pivot)

| Kolom        | Type              | Eigenschappen                    |
|--------------|-------------------|----------------------------------|
| `id`         | bigint            | primary key                      |
| `movie_id`   | bigint            | FK → movies (cascadeOnDelete)    |
| `person_id`  | bigint            | FK → people (cascadeOnDelete)    |
| `character`  | string            | nullable                         |
| `department` | string            | nullable                         |
| `job`        | string            | nullable                         |
| `cast_order` | unsigned smallint | nullable                         |
| `created_at` | timestamp         |                                  |
| `updated_at` | timestamp         |                                  |

Unique: `[movie_id, person_id, job]`

---

### `tv_series_person` (pivot)

| Kolom           | Type              | Eigenschappen                       |
|-----------------|-------------------|-------------------------------------|
| `id`            | bigint            | primary key                         |
| `tv_series_id`  | bigint            | FK → tv_series (cascadeOnDelete)    |
| `person_id`     | bigint            | FK → people (cascadeOnDelete)       |
| `character`     | string            | nullable                            |
| `department`    | string            | nullable                            |
| `job`           | string            | nullable                            |
| `cast_order`    | unsigned smallint | nullable                            |
| `episode_count` | unsigned smallint | nullable                            |
| `created_at`    | timestamp         |                                     |
| `updated_at`    | timestamp         |                                     |

Unique: `[tv_series_id, person_id, job]`

---

### Cascade-deletes

```
Movie      → movie_watches       (cascade)
           → movie_person        (cascade)
TvSeries   → tv_seasons          (cascade)
               → tv_episodes     (cascade)
                   → episode_watches (cascade)
           → tv_series_person    (cascade)
Person     → movie_person        (cascade)
           → tv_series_person    (cascade)
```

---

## 2. Watch date — de drie scenario's

Elke kijkbeurt (film én aflevering) kent drie mogelijke datumstaten:

| Scenario | `watched_at` | `year_only` | Weergave |
|---|---|---|---|
| Exacte datum bekend | `2024-03-15 00:00:00` | `false` | "15 maart 2024" |
| Alleen jaar bekend | `2019-01-01 00:00:00` | `true` | "2019" |
| Geen datum (gewoon gezien) | `null` | `false` | "Datum onbekend" |

### Opslaan

- **Exacte datum**: gebruiker kiest datum → `watched_at = date`, `year_only = false`
- **Alleen jaar**: gebruiker vult jaar in → `watched_at = {jaar}-01-01`, `year_only = true`
- **Geen datum**: leeg laten → `watched_at = null`, `year_only = false`

### Weergeven

```php
public function formattedWatchedAt(): string
{
    if ($this->watched_at === null) {
        return 'Datum onbekend';
    }

    if ($this->year_only) {
        return $this->watched_at->format('Y');
    }

    return $this->watched_at->format('d M Y');
}
```

### UI — modal (radio buttons)

```
[●] Exacte datum   → toont datepicker (standaard geselecteerd, vandaag ingevuld)
[○] Alleen jaar    → toont jaar-input (4 cijfers, min 1900 max 2100)
[○] Geen datum     → datum verborgen
```

### Sortering

Kijkhistorie wordt gesorteerd op `watched_at DESC NULLS LAST` — entries zonder datum komen onderaan.

---

## 3. Models

### `Movie`

```php
protected function casts(): array
{
    return [
        'release_date'     => 'date',
        'genres'           => 'array',
        'last_watched_at'  => 'datetime',
        'first_watched_at' => 'datetime',
    ];
}

public function watches(): HasMany        // → MovieWatch, orderBy watched_at DESC
public function people(): BelongsToMany  // → Person via movie_person
                                         //   withPivot: character, department, job, cast_order
                                         //   orderBy: cast_order ASC

public function posterUrl(): Attribute
public function backdropUrl(): Attribute

public function incrementWatchCount(): void
    // watch_count++, last_watched_at = now(), first_watched_at = now() als null
```

---

### `MovieWatch`

```php
protected function casts(): array
{
    return [
        'watched_at' => 'datetime',
        'year_only'  => 'boolean',
    ];
}

public function movie(): BelongsTo
public function formattedWatchedAt(): string   // zie sectie 2
```

---

### `TvSeries`

```php
protected function casts(): array
{
    return [
        'first_air_date'   => 'date',
        'last_air_date'    => 'date',
        'genres'           => 'array',
        'last_watched_at'  => 'datetime',
        'first_watched_at' => 'datetime',
    ];
}

public function seasons(): HasMany         // → TvSeason, orderBy season_number ASC
public function people(): BelongsToMany   // → Person via tv_series_person
                                          //   withPivot: character, department, job,
                                          //              cast_order, episode_count

public function posterUrl(): Attribute
public function backdropUrl(): Attribute

public function scopeCompleted(Builder $query): Builder   // completion_percentage = 100
public function scopeInProgress(Builder $query): Builder  // 0 < completion_percentage < 100

public function updateProgress(): void
    // episodes_watched = som van alle season->episodes_watched
    // completion_percentage = episodes_watched / number_of_episodes * 100

public function recordWatch(): void
    // last_watched_at = now(), first_watched_at = now() als null, updateProgress()
```

---

### `TvSeason`

```php
protected function casts(): array
{
    return ['air_date' => 'date'];
}

public function series(): BelongsTo
public function episodes(): HasMany    // → TvEpisode, orderBy episode_number ASC
public function posterUrl(): Attribute

public function updateProgress(): void
    // episodes_watched = count van episodes met minstens 1 watch
```

---

### `TvEpisode`

```php
protected function casts(): array
{
    return [
        'air_date'     => 'date',
        'vote_average' => 'float',
    ];
}

public function season(): BelongsTo
public function watches(): HasMany        // → EpisodeWatch

public function isWatched(): Attribute    // bool: heeft minstens 1 watch
public function watchCount(): Attribute   // int: aantal watches

public function addWatch(
    ?Carbon $watchedAt = null,
    bool $yearOnly = false,
    ?string $notes = null,
    ?int $rating = null,
): EpisodeWatch
    // Maakt EpisodeWatch aan
    // Roept season->updateProgress() aan
    // Roept season->series->recordWatch() aan
```

---

### `EpisodeWatch`

```php
protected function casts(): array
{
    return [
        'watched_at' => 'datetime',
        'year_only'  => 'boolean',
    ];
}

public function episode(): BelongsTo
public function formattedWatchedAt(): string
```

---

### `Person`

```php
public function movies(): BelongsToMany    // → Movie via movie_person
public function tvSeries(): BelongsToMany  // → TvSeries via tv_series_person
public function profileUrl(): Attribute
```

---

## 4. TMDB Services

Alle TMDB-calls worden gecached. Cache-duur via `config('tmdb.cache_duration')`.

### `TMDBClient`

Wrapper om de TMDB HTTP API. Gebruikt `config('tmdb.api_key')` en `config('tmdb.base_url')`.

---

### `TMDBMovieService`

```php
public function search(string $query, int $page = 1): array
    // Cache: "tmdb_movie_search_{query}_{page}"
    // Geeft: results[], page, total_pages, total_results

public function getDetails(int $tmdbId): array
    // Cache: "tmdb_movie_details_{tmdbId}"
    // Appends: credits, videos

public function createFromTMDB(int $tmdbId): Movie
    // Velden: title, original_title, overview, poster_path, backdrop_path,
    //         release_date, runtime, vote_average, genres (array), original_language
    // Synct cast via PersonSyncService::syncMovieCast()
```

---

### `TMDBTVService`

```php
public function search(string $query, int $page = 1): array
    // Cache: "tmdb_tv_search_{query}_{page}"

public function getDetails(int $tmdbId): array
    // Cache: "tmdb_tv_details_{tmdbId}"

public function getAggregateCredits(int $tmdbId): array
    // Cache: "tmdb_tv_aggregate_credits_{tmdbId}"

public function getEnglishName(int $tmdbId): ?string
    // Cache: "tmdb_tv_details_en_{tmdbId}"
    // Fetcht naam in en-US locale, geeft null terug als identiek aan origineel

public function getSeasonDetails(int $seriesTmdbId, int $seasonNumber): array
    // Cache: "tmdb_tv_season_{seriesTmdbId}_{seasonNumber}"

public function createFromTMDB(int $tmdbId): TvSeries
    // Velden: name, name_en, original_name, overview, poster_path, backdrop_path,
    //         first_air_date, last_air_date, vote_average, genres, status,
    //         original_language, number_of_seasons, number_of_episodes
    // Synct cast via PersonSyncService::syncTvCast()

public function createSeasonFromTMDB(TvSeries $series, int $seasonNumber): TvSeason
    // Maakt TvSeason + alle TvEpisode records aan

public function clearCache(int $tmdbId): void
    // Verwijdert: details, aggregate_credits, seizoenen 0–50
```

---

### `TMDBImageService`

```php
public function posterUrl(?string $path, string $size = 'medium'): ?string
public function backdropUrl(?string $path, string $size = 'large'): ?string
public function profileUrl(?string $path, string $size = 'medium'): ?string
```

---

### `PersonSyncService`

```php
public function syncMovieCast(Movie $movie, array $credits): void
    // firstOrCreate Person op tmdb_id, synct movie_person pivot

public function syncTvCast(TvSeries $series, array $aggregateCredits): void
    // Zelfde + episode_count in pivot
```

---

### `config/tmdb.php`

```php
return [
    'api_key'        => env('TMDB_API_KEY'),
    'base_url'       => 'https://api.themoviedb.org/3',
    'image_base_url' => 'https://image.tmdb.org/t/p/',
    'cache_duration' => env('TMDB_CACHE_DURATION', 1440),
    'region'         => 'NL',
    'language'       => 'nl-NL',

    'poster_sizes'   => ['small' => 'w185', 'medium' => 'w342', 'large' => 'w500'],
    'backdrop_sizes' => ['small' => 'w300', 'medium' => 'w780', 'large' => 'w1280'],
    'profile_sizes'  => ['small' => 'w45',  'medium' => 'w185', 'large' => 'h632'],
];
```

---

## 5. Actions

### Movies

```
app/Actions/Media/Movies/
├── AddMovieFromTmdb.php          // tmdbId → Movie
├── MarkMovieWatched.php          // Movie + MovieWatchData → MovieWatch
├── DeleteMovieWatch.php          // MovieWatch → void (herbereken watch_count)
└── DeleteMovie.php               // Movie → void
```

### TV Series

```
app/Actions/Media/Tv/
├── AddSeriesFromTmdb.php         // tmdbId → TvSeries (incl. alle seizoenen + afleveringen)
├── MarkEpisodeWatched.php        // TvEpisode + EpisodeWatchData → EpisodeWatch
├── BulkMarkSeriesWatched.php     // TvSeries + EpisodeWatchData → void
├── DeleteEpisodeWatch.php        // EpisodeWatch → void (herbereken voortgang)
├── RefreshSeriesFromTmdb.php     // TvSeries → void (update + nieuwe seizoenen/afleveringen)
└── DeleteSeries.php              // TvSeries → void
```

### DTOs

```php
final readonly class MovieWatchData
{
    public function __construct(
        public ?Carbon  $watchedAt,
        public bool     $yearOnly,
        public ?string  $notes,
        public ?int     $rating,
    ) {}

    public static function fromRequest(MarkMovieWatchedRequest $request): self
}

final readonly class EpisodeWatchData
{
    public function __construct(
        public ?Carbon  $watchedAt,
        public bool     $yearOnly,
        public ?string  $notes,
        public ?int     $rating,
    ) {}

    public static function fromRequest(MarkEpisodeWatchedRequest $request): self
}
```

---

## 6. Controllers & Routes

### Structuur

```
app/Http/Controllers/Media/
├── Movies/
│   ├── MovieController.php          // index, show, store, destroy
│   ├── MovieSearchController.php    // index → JSON (TMDB search)
│   ├── MovieWatchController.php     // store, destroy
│   └── MovieStatsController.php     // index
└── Tv/
    ├── TvSeriesController.php       // index, show, store, destroy
    ├── TvSearchController.php       // index → JSON (TMDB search)
    ├── TvWatchController.php        // store (episode), bulkStore, destroy
    ├── TvRefreshController.php      // store
    └── TvStatsController.php        // index

app/Http/Controllers/
└── PeopleController.php             // show
```

### Validatieregels (alle watch requests)

```php
'watched_at' => ['nullable', 'date', 'before_or_equal:now'],
'year_only'  => ['boolean'],
'notes'      => ['nullable', 'string', 'max:2000'],
'rating'     => ['nullable', 'integer', 'between:1,10'],
```

### Routes

```php
// routes/media.php
Route::prefix('movies')->name('movies.')->group(function () {
    Route::get('/',                      [MovieController::class, 'index'])->name('index');
    Route::get('/stats',                 [MovieStatsController::class, 'index'])->name('stats');
    Route::get('/{movie}',               [MovieController::class, 'show'])->name('show');
    Route::post('/search',               [MovieSearchController::class, 'index'])->name('search');
    Route::post('/',                     [MovieController::class, 'store'])->name('store');
    Route::post('/{movie}/watches',      [MovieWatchController::class, 'store'])->name('watches.store');
    Route::delete('/watches/{watch}',    [MovieWatchController::class, 'destroy'])->name('watches.destroy');
    Route::delete('/{movie}',            [MovieController::class, 'destroy'])->name('destroy');
});

Route::prefix('tv')->name('tv.')->group(function () {
    Route::get('/',                            [TvSeriesController::class, 'index'])->name('index');
    Route::get('/stats',                       [TvStatsController::class, 'index'])->name('stats');
    Route::get('/{series}',                    [TvSeriesController::class, 'show'])->name('show');
    Route::post('/search',                     [TvSearchController::class, 'index'])->name('search');
    Route::post('/',                           [TvSeriesController::class, 'store'])->name('store');
    Route::post('/episodes/{episode}/watches', [TvWatchController::class, 'store'])->name('episodes.watches.store');
    Route::post('/{series}/watches/bulk',      [TvWatchController::class, 'bulkStore'])->name('watches.bulk');
    Route::post('/{series}/refresh',           [TvRefreshController::class, 'store'])->name('refresh');
    Route::delete('/watches/{watch}',          [TvWatchController::class, 'destroy'])->name('watches.destroy');
    Route::delete('/{series}',                 [TvSeriesController::class, 'destroy'])->name('destroy');
});

Route::get('/people/{person}', [PeopleController::class, 'show'])->name('people.show');
```

---

## 7. Views — Movies

### `pages/movies/index.blade.php`

**Header**
- Titel "Movies"
- Knoppen: "Statistics" (secundair) + "Add Movie" (primair)

**Lege state**
- Film-icoon, "No movies yet", "Add Your First Movie" knop

**Met films**
- Zoekbalk: real-time client-side filtering op `data-title` + `data-original-title`
- Grid van filmkaarten, gesorteerd op `last_watched_at DESC, created_at DESC`
- Per kaart:
  - Poster (of placeholder icoon)
  - Titel + releasejaar
  - Watch count, runtime (Xh Ym), TMDB-rating
  - Klikbaar → `movies.show`

**Add Movie modal**
- TMDB-zoekbalk → live resultaten (poster + titel + jaar)
- Klik op resultaat → POST `/movies` met `tmdb_id`
- Sluit automatisch na toevoegen, toast notification

---

### `pages/movies/show.blade.php`

**Header**
- Filmtitel + breadcrumb
- Knoppen: "Add Watch" (primair) + "Delete" (danger, met confirm-dialog)

**Film info**
- Poster (links) + info (rechts):
  - Titel, originele titel, overview
  - Release date, runtime (Xh Ym), TMDB-rating, watch count, last watched
  - Genre-badges

**Directors**
- Kleine ronde profielfoto's + naam + "Director" label
- Gefilterd op `pivot.job === 'Director'`

**Cast**
- Grid: `repeat(auto-fill, minmax(140px, 1fr))`
- Per persoon: profielfoto (2:3), naam, character (pivot)
- Klikbaar → `people.show`
- Standaard max 20 zichtbaar
- **"Show X more" toggle** als meer dan 20: button met chevron-icoon

**Kijkhistorie**
- Gesorteerd op `watched_at DESC NULLS LAST`
- Per watch: datum (via `formattedWatchedAt()`), beoordeling, notities, delete-knop
- Delete via `DELETE /movies/watches/{watch}` + browser confirm-dialog

**Add Watch modal** (zie sectie 2 voor datumlogica)
- Radio: "Exacte datum" (standaard) / "Alleen jaar" / "Geen datum"
- Beoordeling (1–10), notities
- POST naar `/movies/{movie}/watches`
- Sluit automatisch + toast notification

---

## 8. Views — TV Series

### `pages/tv/index.blade.php`

**Header**
- Titel "TV Series"
- Knoppen: "Statistics" + "Add Series"

**Lege state**
- TV-icoon, "No TV series yet", "Add Your First Series" knop

**Met series**
- Zoekbalk: real-time filtering op `data-series-name` + `data-series-original` + `data-series-name-en`
- Grid van series-kaarten, gesorteerd op `last_watched_at DESC`
- Per kaart:
  - Poster (of placeholder)
  - Naam (name_en indien aanwezig)
  - **Completion badge** (absoluut gepositioneerd, rechtsboven): groen badge met `X%` — alleen zichtbaar als `completion_percentage > 0`
  - Voortgang: `X/Y afleveringen`
  - Klikbaar → `tv.show`

---

### `pages/tv/show.blade.php`

**Header**
- Serienaam + breadcrumb
- Knoppen: "Mark All Watched" + "Refresh" (met spinning icoon tijdens laden) + "Delete"

**Serie info**
- Poster + info:
  - Naam, originele naam, name_en (indien aanwezig)
  - Overview
  - First air date, voortgang (`X/Y afleveringen, Z%`), TMDB-rating, status
  - Genre-badges

**Cast** (identiek aan movies, inclusief "show more" toggle)

**Seizoens-accordion**
- Per seizoen:
  - Header (klikbaar): seizoensnaam + `(X/Y episodes watched)` + chevron-icoon
  - Body: initieel verborgen (`display: none`)
  - **localStorage persistentie**: open/dicht staat wordt per serie opgeslagen als `openSeasons_{seriesId}`, geladen bij pageload

- Per aflevering in het seizoen:
  - Nummer + naam
  - Overview (ingekort op 150 tekens)
  - Luchtdatum + runtime
  - **Watch-badges**: per kijkbeurt een badge met datum + delete-knop (fa-times)
  - **"Add Watch" knop** rechts → opent watch-modal voor die aflevering

**Add Watch modal (aflevering)**
- Zelfde datumlogica als films (radio buttons)
- Episodetitel getoond in modal header
- POST naar `/tv/episodes/{episode}/watches`

**Bulk Watch modal**
- Bevestigingstekst: "This will mark ALL episodes of '{serie}' as watched once."
- Datumkeuze (radio, standaard "Alleen jaar")
- Confirm-dialog voor verzenden
- POST naar `/tv/{series}/watches/bulk`
- Toast: "Successfully marked X episodes as watched!"

**Refresh**
- POST naar `/tv/{series}/refresh`
- Knop disabled + spinner tijdens verzoek
- Toast: "Refreshing series data from TMDB..."
- Na succes: "Series refreshed successfully! New episodes may have been added." + pagina reload

---

## 9. Views — People

### `pages/people/show.blade.php`

Bereikbaar via klik op cast-lid in film- of serie-detailpagina.

**Header**
- Naam + breadcrumb (Home → Naam)

**Profiel-kaart**
- Profielfoto (150px) + statistieken-grid:
  - Aantal films
  - Totaal film-kijkbeurten
  - Aantal series
  - Totaal afleveringen gekeken
  - Totale kijktijd (uren)
  - Eerste keer gezien (datum, conditioneel)
  - Laatste keer gezien (datum, conditioneel)

**Films-sectie** (conditioneel, alleen als persoon films heeft)
- Titel: "Films (X)"
- Grid: `repeat(auto-fill, minmax(140px, 1fr))`
- Per film:
  - Poster (2:3) of placeholder
  - Filmtitel
  - Character of job (pivot)
  - Watch count als badge ("Xx") — alleen als > 1
  - Klikbaar → `movies.show`

**TV Series-sectie** (conditioneel)
- Titel: "TV Series (X)"
- Lijst-layout per serie:
  - Poster (50×75px)
  - Naam (name_en indien beschikbaar)
  - Character of job (pivot)
  - Afleveringen in serie (pivot.episode_count)
  - Afleveringen gekeken (groen)
  - Completion percentage
  - Laatste kijkdatum (rechts)
  - Klikbaar → `tv.show`

**Controller: `PeopleController@show`**
```php
public function show(Person $person): View
{
    $movies    = $person->movies()->with('watches')->get();
    $tvSeries  = $person->tvSeries()->with(['seasons.episodes.watches'])->get();

    return view('pages.people.show', [
        'person'               => $person,
        'movies'               => $movies,
        'totalMovieWatches'    => $movies->sum(fn ($m) => $m->watches->count()),
        'totalEpisodesWatched' => $tvSeries->sum('episodes_watched'),
        'totalHours'           => $this->calculateTotalHours($movies, $tvSeries),
        'firstSeen'            => $this->firstSeen($movies, $tvSeries),
        'lastSeen'             => $this->lastSeen($movies, $tvSeries),
    ]);
}
```

---

## 10. Statistieken — Movies

### `pages/movies/stats.blade.php`

**Hero stat**: totale kijktijd in uren

**Grid — 4 overzichtscijfers**
- Totaal films
- Totaal kijkbeurten
- Gemiddelde beoordeling
- Uniek gekeken films

**First & Last Watch** (2 kolommen)
- Poster + filmtitel + datum
- Klikbaar → `movies.show`

**Meest gekeken films** (top 10)
- Grid met poster, titel, watch count badge, rating, laatste kijkdatum

**Genre-verdeling**
- Max 10 genres
- Per genre: naam + count

**Recente kijkhistorie**
- Max 10 dagen
- Per dag: datum + aantal kijkbeurten + filmtitels

---

## 11. Statistieken — TV Series

### `pages/tv/stats.blade.php`

Dit is de meest uitgebreide pagina van de module. Bevat 21 secties met Chart.js-visualisaties.

**1. Hero stat**: totale kijktijd in uren + totaal afleveringen

**2. Overzicht-grid** (3 kaarten)
- Totaal series + in progress
- Totaal afleveringen gekeken van totaal
- Overall completion % + volledig afgerond

**3. First & Last Watch** (2 kolommen)
- Poster + serienaam + S##E## episode + datum

**4. Meest gekeken series** (top 10)
- Grid: poster, naam, afleveringen, total watches, total hours

**5. Watch time per dag** (Chart.js bar chart)
- Periodefilter buttons: 30 / 60 / 90 / 180 / 360 dagen + Custom
- Custom range: twee datuminputs
- Toont: totale uren in periode + gemiddeld uren/dag
- Max 12 labels op x-as

**6. Activity heatmap** (laatste 365 dagen)
- GitHub-stijl contribution graph
- 5 kleursniveaus op basis van count
- Tooltip per dag: datum + aantal afleveringen
- Maandlabels + legende (Less → More)

**7. Uur-verdeling**
- Bar chart over 24 uur (00:00–23:00)
- Gradient kleuren

**8. Weekdag vs. weekend**
- Twee grote percentages naast elkaar

**9. Cumulatieve kijktijd**
- Line chart: totale uren over tijd (oplopend)

**10. Rating vs. kijktijd** (scatter plot)
- X-as: vote_average, Y-as: totale kijkuren per serie
- Hover-tooltip: "Serienaam — ⭐ 8.5 / 123 hrs"

**11. Abandoned series**
- Lijst van series met voortgangsbalk (%) die niet 100% zijn
- Gradient progress bar

**12. Dagen om serie af te ronden**
- Horizontale bar chart
- Klikbare balken → `tv.show`
- Tooltip: "X days — Y eps — Z eps/day"

**13. Langste pauze binnen een serie**
- Ranked lijst: serienaam + aantal dagen

**14. Completion progress**
- Per serie: naam (klikbaar) + progress bar + "X van Y episodes"

**15. Genre-verdeling**
- Identiek aan movies stats

**16. Recente kijkhistorie**
- Max 10 dagen + afleveringtitels

**17. Kijkpatronen**
- Meest actieve dag
- Langste streak (aaneengesloten kijkdagen)
- Gemiddeld afleveringen/dag
- Binge sessions (5+ afleveringen op één dag): datum + count-badges

**18. Tijdgebaseerde statistieken**
- Drukste maand + drukste jaar
- Gemiddelde afleveringlengte (minuten)
- Kijktijd per jaar (lijst: jaar → uren)
- Meest herbekeken afleveringen (serie, S##E##, naam, watch count)

**19. Content breakdown**
- Completion rate %
- Populairste decennium (bijv. "1990s")
- Afleveringsaantal-verdeling (bijv. "1–10 eps" → 5 series)

**20. Persoonlijke records**
- Eerste + laatste aflevering per jaar (serie, episode, datum)
- Hoogst beoordeelde series (grid met posters + rating)
- Langste voltooide series (op afleveringsaantal)
- Snelste binges (X dagen, Y afleveringen)

**21. Vergelijkingen**
- Gekeken vs. ongekeken uren (+ percentages)
- Seizoen-completion: volledig / gedeeltelijk / niet begonnen
- Year-over-year groei (lijst met % in groen/rood)

---

## 12. Gedeelde UI-patronen

### Toast notifications

- Positie: fixed top-right
- Groen (#10b981) voor success, rood (#ef4444) voor error
- Verdwijnt automatisch na 3 seconden
- Slide-in animatie (translateX 100% → 0)

### Modals

- Overlay met `backdrop` (klik sluit modal)
- Close-knop (fa-times) rechtsboven
- Enter-toets submit in formulier-modals

### Delete-bevestiging

- Browser `confirm()` dialog voor destructieve acties
- "Are you sure you want to delete X?" patroon

### Lege states

Elke sectie die conditioneel is toont een icoon + beschrijvende tekst als er geen data is.

### Client-side zoeken

Beide index-pagina's filteren real-time op `data-*` attributen via een `oninput`-handler.
Geen server-request — filtert direct de DOM.

---

## 13. Frontend dependencies

| Dependency | Gebruik |
|---|---|
| **Chart.js** | Bar, line, scatter charts op stats-pagina's |
| **Alpine.js** | Modal state, toggle-interacties |

Chart.js laden via CDN of npm:
```js
// resources/js/modules/charts.js
import Chart from 'chart.js/auto';
```

Kleuren-constanten voor charts (consistent door hele stats-pagina):
```js
const PINK   = '#fa709a';
const YELLOW = '#fee140';
const PURPLE = '#764ba2';
const BLUE   = '#4facfe';
const GRID   = 'rgba(255,255,255,0.06)';
const TICK   = 'rgba(255,255,255,0.5)';
```

localStorage voor seizoen-state:
```js
// Sleutel per serie: `openSeasons_{seriesId}`
// Waarde: JSON array van open season IDs
```

---

## 14. Omgevingsvariabelen

Toevoegen aan `.env` én `.env.example`:

```env
TMDB_API_KEY=
TMDB_CACHE_DURATION=1440
```

| Variabele | Verplicht | Default | Beschrijving |
|---|---|---|---|
| `TMDB_API_KEY` | ja | — | API key van themoviedb.org |
| `TMDB_CACHE_DURATION` | nee | `1440` | Cache-duur in minuten (1440 = 24 uur) |

---

## 15. Implementatievolgorde

```
Fase 1 — Database & Models
 1.  php artisan make:model Person -mf
 2.  php artisan make:model Movie -mf
 3.  php artisan make:model MovieWatch -mf
 4.  php artisan make:model TvSeries -mf
 5.  php artisan make:model TvSeason -mf
 6.  php artisan make:model TvEpisode -mf
 7.  php artisan make:model EpisodeWatch -mf
 8.  Migraties schrijven (alle tabellen + pivot-tabellen)
 9.  Models invullen: casts, relaties, scopes, methoden, accessors

Fase 2 — TMDB integratie
10.  config/tmdb.php aanmaken
11.  TMDBClient aanmaken
12.  TMDBImageService aanmaken
13.  PersonSyncService aanmaken
14.  TMDBMovieService aanmaken
15.  TMDBTVService aanmaken

Fase 3 — Actions & Requests
16.  Form Requests aanmaken (alle watch + store requests)
17.  DTOs aanmaken (MovieWatchData, EpisodeWatchData)
18.  Movie Actions aanmaken
19.  TV Actions aanmaken

Fase 4 — Controllers & Routes
20.  Movie controllers aanmaken
21.  TV controllers aanmaken
22.  PeopleController aanmaken
23.  routes/media.php aanmaken + importeren in bootstrap/app.php

Fase 5 — Views Films
24.  pages/movies/index.blade.php (grid + TMDB search modal)
25.  pages/movies/show.blade.php (info + cast + kijkhistorie + modals)
26.  pages/movies/stats.blade.php

Fase 6 — Views Series
27.  pages/tv/index.blade.php
28.  pages/tv/show.blade.php (seizoen-accordion + localStorage + modals)
29.  pages/tv/stats.blade.php (21 secties + Chart.js)

Fase 7 — People & Afwerking
30.  pages/people/show.blade.php
31.  SCSS: resources/css/features/_media.scss + importeren
32.  Chart.js installeren + charts module aanmaken
33.  Artisan command: media:backfill-tv-english-names
34.  php artisan make:test Media/MovieTest --pest
35.  php artisan make:test Media/TvSeriesTest --pest
36.  php artisan make:test Media/PeopleTest --pest
37.  vendor/bin/pint --dirty
```

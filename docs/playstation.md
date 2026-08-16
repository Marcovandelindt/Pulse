# PlayStation Activiteit – Implementatieplan

Dit document beschrijft hoe de PlayStation activiteitstracking in Aura is opgebouwd, zodat het systeem in een nieuw project overgenomen kan worden.

---

## Overzicht

Het systeem scrapt speeldata van **PS-Timetracker** (ps-timetracker.com), slaat games en sessies op in de database, en toont statistieken en analytics aan de gebruiker.

Er wordt **geen officiële PSN API** gebruikt – alles werkt via webscraping van een derde partij.

---

## 1. Database

### Tabel: `play_station_games`

| Kolom | Type | Beschrijving |
|---|---|---|
| `id` | bigint | Primary key |
| `name` | string | Naam van de game |
| `platform` | string | PS3 / PS4 / PS5 / PSVITA |
| `image_url` | string (nullable) | URL van de cover |
| `hours` | decimal | Totaal gespeeld (uren) |
| `sessions` | integer | Aantal sessies |
| `avg_session_minutes` | integer | Gemiddelde sessieduur |
| `last_played_at` | date (nullable) | Laatste speeldatum |
| `trophies` | integer | Aantal trofeeën |
| `completion_percentage` | decimal | Voltooiingspercentage |
| `psn_url` | string (nullable) | PSN profiel URL |
| `price` | decimal (nullable) | Aankoopprijs |
| `manual_minutes` | integer | Handmatig ingevoerde speeltijd |
| `exclude_from_sync` | boolean | Niet meer automatisch syncen |
| `backlog_status` | string (nullable) | Not Started / In Progress / Completed / etc. |
| `user_rating` | decimal (nullable) | Eigen beoordeling |
| `critic_rating` | decimal (nullable) | Critici beoordeling |
| `play_mode` | string (nullable) | Single-player / Co-op / etc. (Enum) |
| `main_story_completed` | boolean | Hoofdverhaal uitgespeeld |

**Unique constraint:** `(name, platform)` – voorkomt duplicaten per platform.

### Tabel: `play_station_sessions`

| Kolom | Type | Beschrijving |
|---|---|---|
| `id` | bigint | Primary key |
| `play_station_game_id` | foreign key | Koppeling naar game |
| `started_at` | datetime | Start van de sessie |
| `ended_at` | datetime (nullable) | Einde van de sessie |
| `duration_minutes` | integer | Duur in minuten |

**Unique constraint:** `(play_station_game_id, started_at)`  
**Index:** op `started_at`  
**Cascade:** delete sessies wanneer game wordt verwijderd.

---

## 2. Models

### `PlayStationGame`

- Relatie: `sessions()` → `HasMany(PlayStationSession)`
- Computed attributes:
  - `getCalculatedHoursAttribute()` – totale uren (sessies + handmatig)
  - `getFormattedHoursAttribute()` – bijv. "24.5h"
  - `getCalculatedSessionsAttribute()` – aantal sessies
  - `getFormattedAvgSessionAttribute()` – gemiddelde duur
  - `getManualMinutesFormattedAttribute()` – opgemaakte handmatige tijd
- Casts: `play_mode` → `PlayMode` enum, `backlog_status` → `BacklogStatus` enum
- Trait: `HasBacklogStatus` (voor backlog-integratie, optioneel)

### `PlayStationSession`

- Relatie: `game()` → `BelongsTo(PlayStationGame)`
- Casts: `started_at`, `ended_at` → `datetime`, `duration_minutes` → `integer`
- Query scopes:
  - `recent($limit = 10)` – meest recente sessies
  - `today()` – sessies van vandaag
  - `thisWeek()` – sessies van deze week
- Computed: `getFormattedDurationAttribute()` – bijv. "2h 45m"

---

## 3. Service: `PlayStationScraperService`

**Locatie:** `app/Services/PlayStation/PlayStationScraperService.php`

De service scrapt data van `https://ps-timetracker.com`. Authenticatie verloopt via een sessiecookie (`_my_app_session`).

### Methoden

| Methode | Beschrijving |
|---|---|
| `scrapeGames($username)` | Haalt gamelijst op (naam, platform, uren, sessies, etc.) |
| `syncGames($username)` | Slaat games op/bij in de database |
| `scrapeSessions($username)` | Haalt individuele sessies op per game |
| `syncSessions($username)` | Slaat sessies op in de database |
| `setSessionCookie($cookie)` | Zet authenticatiecookie |

### Technische details

- Gebruikt `Http::withoutVerifying()` (SSL bypass)
- HTML-parsing via **Symfony DomCrawler**
- Data zit in een HTML-tabel; worden geparst via DOM-selectoren
- Sessietijden worden met regex-patronen geparsed
- Bij `syncSessions`: standaard worden alleen nieuwe sessies gesynchroniseerd; met `--all` worden alle pagina's gesynchroniseerd

---

## 4. Console Commands

| Command | Beschrijving |
|---|---|
| `php artisan playstation:sync [username]` | Sync gamelijst |
| `php artisan playstation:sync-sessions [username] [--cookie=] [--save-cookie] [--all]` | Sync sessies |
| `php artisan playstation:debug [username]` | Debug scraper vs. database |
| `php artisan playstation:test-scraper` | Test de scraper |

### Config

Gebruikersnaam en cookie worden opgeslagen in `config/services.php`:
```php
'playstation' => [
    'username' => env('PLAYSTATION_USERNAME'),
    'cookie' => ..., // opgeslagen via --save-cookie
],
```

---

## 5. Controllers

### `PlayStationController`

**Locatie:** `app/Http/Controllers/Gaming/PlayStationController.php`

| Methode | Route | Beschrijving |
|---|---|---|
| `index()` | `GET /playstation` | Gamelijst met zoeken/filteren/sorteren |
| `create()` | `GET /playstation/create` | Formulier nieuwe game |
| `store()` | `POST /playstation` | Opslaan nieuwe game (incl. afbeelding upload) |
| `show()` | `GET /playstation/games/{game}` | Gamedetailpagina |
| `edit()` | `GET /playstation/games/{game}/edit` | Bewerken game |
| `update()` | `PUT /playstation/games/{game}` | Opslaan wijzigingen |
| `sessions()` | `GET /playstation/sessions` | Alle sessies + statistieken |
| `sync()` | `POST /playstation/sync` | Handmatig syncen triggeren |
| `convertToManual()` | `POST /playstation/games/{game}/convert-to-manual` | Zet game op handmatig bijhouden |
| `watDeedIk()` | `GET /playstation/wat-deed-ik` | JSON: speeltijd per game op een datum |

### `PlayStationStatsController`

**Locatie:** `app/Http/Controllers/Gaming/PlayStationStatsController.php`

Verwerkt statistieken per tijdsperiode (30 dagen, 7 dagen, 1 dag, of aangepast):

- `daily_playtime[]` – uren en sessies per dag
- `top_games[]` – top 5 games op speeltijd
- `total_hours` – totaal in periode
- `average_session_duration` – gemiddelde sessieduur
- `longest_session` – langste sessie
- `game_hopping_rate` – hoe vaak van game gewisseld per uur

---

## 6. Routes

**Bestand:** `routes/gaming.php`  
**Prefix:** `/playstation`  
**Middleware:** auth (of vergelijkbaar)

```php
Route::prefix('playstation')->name('playstation.')->group(function () {
    Route::get('/', [PlayStationController::class, 'index'])->name('index');
    Route::get('/create', [PlayStationController::class, 'create'])->name('create');
    Route::post('/', [PlayStationController::class, 'store'])->name('store');
    Route::get('/sessions', [PlayStationController::class, 'sessions'])->name('sessions');
    Route::get('/stats', [PlayStationStatsController::class, 'index'])->name('stats');
    Route::get('/games/{game}', [PlayStationController::class, 'show'])->name('games.show');
    Route::get('/games/{game}/edit', [PlayStationController::class, 'edit'])->name('games.edit');
    Route::put('/games/{game}', [PlayStationController::class, 'update'])->name('games.update');
    Route::post('/games/{game}/convert-to-manual', [PlayStationController::class, 'convertToManual']);
    Route::post('/sync', [PlayStationController::class, 'sync'])->name('sync');
    Route::get('/wat-deed-ik', [PlayStationController::class, 'watDeedIk'])->name('wat-deed-ik');
});
```

---

## 7. Views

**Directory:** `resources/views/playstation/`

| Bestand | Beschrijving |
|---|---|
| `index.blade.php` | Gamelijst met zoeken, platform-filter, sorteeroptie, statistieken-kaarten, recente sessies |
| `sessions.blade.php` | Alle sessies, statistieken (langste, kortste, streak, top-uren), paginatie |
| `show.blade.php` | Gamedetail: header met afbeelding/platform, backlogstatus, maandelijkse stats, sessiegeschiedenis |
| `stats.blade.php` | Analyticsdashboard: dagelijkse grafiek, top games, sessiestatistieken, game hopping rate |
| `edit.blade.php` | Bewerkingsformulier: prijs, handmatige speeltijd, play mode, ratings, genres |
| `create.blade.php` | Formulier voor handmatig toevoegen: naam, platform, afbeelding, prijs |

### Platform kleurcodes
- PS5: `#003087`
- PS4: `#00439c`
- PS3/PSVITA: eigen kleur

---

## 8. Volgorde van implementatie

1. **Migraties** aanmaken: `play_station_games` → `play_station_sessions` + de extra kolommen
2. **Models** aanmaken: `PlayStationGame` en `PlayStationSession` met relaties, casts en computed attributes
3. **Service** aanmaken: `PlayStationScraperService` met scrape- en synclogica  
   - Vereist: `composer require symfony/dom-crawler symfony/css-selector`
4. **Console Commands** aanmaken: sync games, sync sessions, debug, test
5. **Config** toevoegen: `config/services.php` met PlayStation-sleutels + `.env` variabelen
6. **Controllers** aanmaken: `PlayStationController` en `PlayStationStatsController`
7. **Routes** registreren in `routes/gaming.php` (of `routes/web.php`)
8. **Views** aanmaken: 6 Blade-templates

---

## 9. Dependencies

| Package | Gebruik |
|---|---|
| `symfony/dom-crawler` | HTML-parsing voor scraper |
| `symfony/css-selector` | CSS-selectors in DomCrawler |
| Laravel `Http` facade | HTTP-requests naar PS-Timetracker |

---

## 10. Optionele integraties (uit Aura)

- **BacklogStatus enum** – voor het bijhouden van de spelstatus
- **PlayMode enum** – single-player / co-op / etc.
- **HasBacklogStatus trait** – gedeeld met andere media-typen (boeken, films, etc.)
- **Afbeelding upload** – via Laravel `Storage` naar een `playstation`-disk
- **`wat-deed-ik` JSON endpoint** – wordt gebruikt door een dagoverzicht-feature elders in de app


## Example Scraper Class
<?php

namespace App\Services\PlayStation;

use App\Models\PlayStationGame;
use App\Models\PlayStationSession;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class PlayStationScraperService
{
    private const BASE_URL = 'https://ps-timetracker.com';

    public function __construct(
        private ?string $sessionCookie = null
    ) {}

    public function setSessionCookie(string $cookie): self
    {
        $this->sessionCookie = $cookie;

        return $this;
    }

    /**
     * Test if the session cookie is valid by fetching playtimes page
     */
    public function testConnection(string $username): array
    {
        $url = self::BASE_URL."/profile/{$username}/playtimes";

        $response = Http::withoutVerifying()->withHeaders([
            'Cookie' => '_my_app_session='.$this->sessionCookie,
        ])->get($url);

        if (! $response->successful()) {
            return [
                'success' => false,
                'error' => 'HTTP Error: '.$response->status(),
            ];
        }

        $html = $response->body();

        // Check if we're redirected to login page
        if (str_contains($html, 'Your PSN-Name') && str_contains($html, 'Your Code')) {
            return [
                'success' => false,
                'error' => 'Session cookie is invalid or expired',
            ];
        }

        // Try to extract some data to confirm it works
        $crawler = new Crawler($html);

        // Return raw HTML snippet for analysis
        return [
            'success' => true,
            'html_length' => strlen($html),
            'title' => $crawler->filter('title')->count() ? $crawler->filter('title')->text() : 'No title',
            'html_preview' => substr($html, 0, 2000),
        ];
    }

    /**
     * Fetch and parse all games from public profile
     */
    public function scrapeGames(string $username): array
    {
        $url = self::BASE_URL."/profile/{$username}";

        $response = Http::withoutVerifying()->get($url);

        if (! $response->successful()) {
            return [
                'success' => false,
                'error' => 'HTTP Error: '.$response->status(),
                'games' => [],
            ];
        }

        $crawler = new Crawler($response->body());

        $games = [];
        $crawler->filter('table tbody tr')->each(function (Crawler $row) use (&$games) {
            $cells = $row->filter('td');

            // Table structure: #, Image, Name, Platform, Hours, Sessions, Avg, Last Played, Trophies, %
            if ($cells->count() >= 8) {
                // Extract image URL from the image cell
                $imageUrl = null;
                $imageNode = $cells->eq(1)->filter('img');
                if ($imageNode->count()) {
                    $imageUrl = $imageNode->attr('src');
                }

                // Extract game URL from the name link
                $gameUrl = null;
                $linkNode = $cells->eq(2)->filter('a');
                if ($linkNode->count()) {
                    $gameUrl = $linkNode->attr('href');
                }

                // Parse hours (e.g., "586h" -> 586.0, "55m" -> 0.9)
                $hoursText = trim($cells->eq(4)->text());
                $hours = $this->parsePlaytime($hoursText);

                // Parse sessions
                $sessions = (int) preg_replace('/[^0-9]/', '', trim($cells->eq(5)->text()));

                // Parse average session (e.g., "41m" or "1h 30m")
                $avgText = trim($cells->eq(6)->text());
                $avgMinutes = $this->parseAvgSession($avgText);

                // Parse last played date
                $lastPlayedText = trim($cells->eq(7)->text());
                $lastPlayedAt = $this->parseLastPlayed($lastPlayedText);

                // Parse trophies and completion if available
                $trophies = null;
                $completion = null;
                if ($cells->count() >= 10) {
                    $trophies = (int) preg_replace('/[^0-9]/', '', trim($cells->eq(8)->text())) ?: null;
                    $completionText = trim($cells->eq(9)->text());
                    $completion = (float) preg_replace('/[^0-9.]/', '', $completionText) ?: null;
                }

                $games[] = [
                    'name' => trim($cells->eq(2)->text()),
                    'platform' => trim($cells->eq(3)->text()),
                    'image_url' => $imageUrl,
                    'hours' => $hours,
                    'sessions' => $sessions,
                    'avg_session_minutes' => $avgMinutes,
                    'last_played_at' => $lastPlayedAt,
                    'trophies' => $trophies,
                    'completion_percentage' => $completion,
                    'psn_url' => $gameUrl ? self::BASE_URL.$gameUrl : null,
                ];
            }
        });

        return [
            'success' => true,
            'games_count' => count($games),
            'games' => $games,
        ];
    }

    /**
     * Sync games from profile to database
     */
    public function syncGames(string $username): array
    {
        $result = $this->scrapeGames($username);

        if (! $result['success']) {
            return $result;
        }

        $games = $result['games'];

        if (empty($games)) {
            return [
                'success' => true,
                'synced' => 0,
                'message' => 'No games found to sync',
            ];
        }

        // Get games excluded from sync
        $excludedGames = PlayStationGame::where('exclude_from_sync', true)
            ->get()
            ->map(fn ($game) => $game->name.'|'.$game->platform)
            ->toArray();

        // Filter out excluded games
        $gamesToSync = array_filter($games, function ($game) use ($excludedGames) {
            return ! in_array($game['name'].'|'.$game['platform'], $excludedGames);
        });

        $skipped = count($games) - count($gamesToSync);

        if (empty($gamesToSync)) {
            return [
                'success' => true,
                'synced' => 0,
                'skipped' => $skipped,
                'message' => 'No games to sync (all excluded)',
            ];
        }

        // Upsert games that are not excluded
        PlayStationGame::upsert(
            array_values($gamesToSync),
            ['name', 'platform'], // Unique keys
            ['image_url', 'hours', 'sessions', 'avg_session_minutes', 'last_played_at', 'trophies', 'completion_percentage', 'psn_url'] // Update columns
        );

        return [
            'success' => true,
            'synced' => count($gamesToSync),
            'skipped' => $skipped,
            'message' => 'Successfully synced '.count($gamesToSync).' games'.($skipped > 0 ? " ({$skipped} excluded)" : ''),
        ];
    }

    /**
     * Parse playtime string to hours (e.g., "586h" -> 586.0, "55m" -> 0.9)
     */
    private function parsePlaytime(string $text): float
    {
        if (empty($text) || $text === '-') {
            return 0.0;
        }

        $hours = 0.0;

        // Match hours (e.g., "586h" or "586")
        if (preg_match('/(\d+)\s*h/i', $text, $matches)) {
            $hours += (float) $matches[1];
        }

        // Match minutes (e.g., "55m")
        if (preg_match('/(\d+)\s*m/i', $text, $matches)) {
            $hours += (float) $matches[1] / 60;
        }

        // If no unit found, check if it's just a number (assume hours for large numbers, minutes for small)
        if ($hours === 0.0 && preg_match('/^(\d+)$/', $text, $matches)) {
            $value = (float) $matches[1];
            // Assume it's hours if no unit specified
            $hours = $value;
        }

        return round($hours, 1);
    }

    /**
     * Parse average session time to minutes
     */
    private function parseAvgSession(string $text): ?int
    {
        if (empty($text) || $text === '-') {
            return null;
        }

        $minutes = 0;

        // Match hours (e.g., "1h")
        if (preg_match('/(\d+)h/', $text, $matches)) {
            $minutes += (int) $matches[1] * 60;
        }

        // Match minutes (e.g., "30m")
        if (preg_match('/(\d+)m/', $text, $matches)) {
            $minutes += (int) $matches[1];
        }

        return $minutes > 0 ? $minutes : null;
    }

    /**
     * Parse last played date string to Carbon date
     */
    private function parseLastPlayed(string $text): ?string
    {
        if (empty($text) || $text === '-') {
            return null;
        }

        try {
            // Handle relative dates like "Today", "Yesterday"
            if (stripos($text, 'today') !== false) {
                return Carbon::today()->toDateString();
            }
            if (stripos($text, 'yesterday') !== false) {
                return Carbon::yesterday()->toDateString();
            }

            // Try to parse as date
            return Carbon::parse($text)->toDateString();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Fetch public profile data (no auth needed) - for testing
     */
    public function getPublicProfile(string $username): array
    {
        $result = $this->scrapeGames($username);

        if (! $result['success']) {
            return $result;
        }

        return [
            'success' => true,
            'games_count' => $result['games_count'],
            'games' => array_slice($result['games'], 0, 5), // First 5 for preview
        ];
    }

    /**
     * Sync sessions from playtimes page (requires authentication)
     */
    public function syncSessions(string $username): array
    {
        if (! $this->sessionCookie) {
            $this->sessionCookie = Setting::getPlayStationSessionCookie();
        }

        if (! $this->sessionCookie) {
            return [
                'success' => false,
                'error' => 'No session cookie configured',
            ];
        }

        // Build game lookup map (name+platform -> id)
        $gameMap = PlayStationGame::all()
            ->keyBy(fn ($game) => $game->name.'|'.$game->platform)
            ->map(fn ($game) => $game->id)
            ->toArray();

        if (empty($gameMap)) {
            return [
                'success' => false,
                'error' => 'No games found. Please sync games first.',
            ];
        }

        // Get the most recent session date to compare
        $latestSession = PlayStationSession::orderByDesc('started_at')->first();
        $latestSessionDate = $latestSession?->started_at;

        $totalSynced = 0;
        $totalSkipped = 0;
        $totalExisting = 0;
        $totalGamesCreated = 0;
        $updatedGameIds = [];
        $page = 1;
        $hasMorePages = true;

        while ($hasMorePages) {
            $result = $this->scrapeSessionsPage($username, $page);

            if (! $result['success']) {
                if ($page === 1) {
                    return $result;
                }
                break;
            }

            if (empty($result['sessions'])) {
                break;
            }

            $pageHasNewSessions = false;

            foreach ($result['sessions'] as $sessionData) {
                $gameKey = $sessionData['game_name'].'|'.$sessionData['platform'];
                $gameId = $gameMap[$gameKey] ?? null;

                if (! $gameId) {
                    // Create the game if it doesn't exist
                    $game = PlayStationGame::create([
                        'name' => $sessionData['game_name'],
                        'platform' => $sessionData['platform'],
                    ]);

                    $gameId = $game->id;
                    $gameMap[$gameKey] = $gameId;
                    $totalGamesCreated++;

                    Log::info("Created new PlayStation game: {$sessionData['game_name']} ({$sessionData['platform']})");
                }

                // Parse the session start time
                $sessionStart = Carbon::parse($sessionData['started_at']);

                // Skip if this session is older than our latest stored session
                if ($latestSessionDate && $sessionStart->lte($latestSessionDate)) {
                    // Check if it exists (might be the exact same session)
                    $exists = PlayStationSession::where('play_station_game_id', $gameId)
                        ->where('started_at', $sessionData['started_at'])
                        ->exists();

                    if ($exists) {
                        $totalExisting++;

                        continue;
                    }
                }

                // Try to insert (might fail on duplicate)
                try {
                    PlayStationSession::create([
                        'play_station_game_id' => $gameId,
                        'started_at' => $sessionData['started_at'],
                        'ended_at' => $sessionData['ended_at'],
                        'duration_minutes' => $sessionData['duration_minutes'],
                    ]);

                    $totalSynced++;
                    $pageHasNewSessions = true;
                    $updatedGameIds[$gameId] = true;
                } catch (\Illuminate\Database\QueryException $e) {
                    // Duplicate entry, skip
                    $totalExisting++;
                }
            }

            // Stop if this entire page had no new sessions (all older than latest)
            if (! $pageHasNewSessions && $latestSessionDate) {
                break;
            }

            $hasMorePages = $result['has_more'];
            $page++;

            // Safety limit
            if ($page > 50) {
                break;
            }
        }

        // Update last_played_at for any game that received new sessions
        foreach (array_keys($updatedGameIds) as $gameId) {
            $maxStartedAt = PlayStationSession::where('play_station_game_id', $gameId)->max('started_at');
            if ($maxStartedAt) {
                PlayStationGame::where('id', $gameId)->update([
                    'last_played_at' => Carbon::parse($maxStartedAt)->toDateString(),
                ]);
            }
        }

        return [
            'success' => true,
            'synced' => $totalSynced,
            'skipped' => $totalSkipped,
            'games_created' => $totalGamesCreated,
            'pages' => $page - 1,
            'message' => "Synced {$totalSynced} sessions from ".($page - 1).' pages'.($totalGamesCreated > 0 ? " ({$totalGamesCreated} new games created)" : ''),
        ];
    }

    /**
     * Scrape a single page of sessions
     */
    private function scrapeSessionsPage(string $username, int $page = 1): array
    {
        $url = self::BASE_URL."/profile/{$username}/playtimes?page={$page}";

        $response = Http::withoutVerifying()->withHeaders([
            'Cookie' => '_my_app_session='.$this->sessionCookie,
        ])->get($url);

        if (! $response->successful()) {
            return [
                'success' => false,
                'error' => 'HTTP Error: '.$response->status(),
            ];
        }

        $html = $response->body();

        // Check if we're redirected to login page
        if (str_contains($html, 'Your PSN-Name') && str_contains($html, 'Your Code')) {
            return [
                'success' => false,
                'error' => 'Session cookie is invalid or expired',
            ];
        }

        $crawler = new Crawler($html);
        $sessions = [];

        // Parse session rows from table
        $crawler->filter('table tbody tr')->each(function (Crawler $row) use (&$sessions) {
            $cells = $row->filter('td');
            $cellCount = $cells->count();

            if ($cellCount >= 6) {
                // Column structure: Empty, Game, Platform, Duration, Start, End, Edit
                $gameName = trim($cells->eq(1)->text());
                $platform = trim($cells->eq(2)->text());
                $durationText = trim($cells->eq(3)->text());
                $startText = trim($cells->eq(4)->text());
                $endText = trim($cells->eq(5)->text());

                $startedAt = $this->parseSessionDateTime($startText);
                $endedAt = $this->parseSessionDateTime($endText);
                $durationMinutes = $this->parseSessionDuration($durationText);

                if ($gameName && $startedAt && $durationMinutes > 0) {
                    $sessions[] = [
                        'game_name' => $gameName,
                        'platform' => $platform,
                        'started_at' => $startedAt,
                        'ended_at' => $endedAt,
                        'duration_minutes' => $durationMinutes,
                    ];
                }
            }
        });

        // Check for next page
        $hasMore = $crawler->filter('a[rel="next"]')->count() > 0;

        return [
            'success' => true,
            'sessions' => $sessions,
            'has_more' => $hasMore,
            'page' => $page,
        ];
    }

    /**
     * Parse session datetime string
     */
    private function parseSessionDateTime(string $text): ?string
    {
        if (empty($text) || $text === '-') {
            return null;
        }

        try {
            return Carbon::parse($text)->toDateTimeString();
        } catch (\Exception $e) {
            Log::warning('Failed to parse session datetime: '.$text);

            return null;
        }
    }

    /**
     * Parse session duration to minutes
     * Handles: "17 minutes", "1:01 hours", "1 minute", "2h 30m"
     */
    private function parseSessionDuration(string $text): int
    {
        if (empty($text) || $text === '-') {
            return 0;
        }

        $minutes = 0;

        // Handle "X:XX hours" format (e.g., "1:01 hours")
        if (preg_match('/(\d+):(\d+)\s*hours?/i', $text, $matches)) {
            $minutes = (int) $matches[1] * 60 + (int) $matches[2];

            return $minutes;
        }

        // Handle "X hours" or "X hour"
        if (preg_match('/(\d+)\s*hours?/i', $text, $matches)) {
            $minutes += (int) $matches[1] * 60;
        }

        // Handle "X minutes" or "X minute"
        if (preg_match('/(\d+)\s*minutes?/i', $text, $matches)) {
            $minutes += (int) $matches[1];
        }

        // Handle shorthand "Xh" or "Xm"
        if (preg_match('/(\d+)\s*h\b/i', $text, $matches)) {
            $minutes += (int) $matches[1] * 60;
        }

        if (preg_match('/(\d+)\s*m\b/i', $text, $matches)) {
            $minutes += (int) $matches[1];
        }

        return $minutes;
    }
}

<?php

declare(strict_types=1);

namespace App\Services\PlayStation;

use App\Models\PlayStationGame;
use App\Models\PlayStationSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

final class PlayStationScraperService
{
    private const BASE_URL = 'https://ps-timetracker.com';

    private ?string $cookie = null;

    public function setSessionCookie(string $cookie): void
    {
        $this->cookie = $cookie;
    }

    public function scrapeGames(string $username): array
    {
        $response = Http::withoutVerifying()
            ->withHeaders($this->headers())
            ->get(self::BASE_URL."/profile/{$username}");

        if (! $response->successful()) {
            return [];
        }

        $crawler = new Crawler($response->body());
        $games = [];

        // Table structure: #, Image, Name, Platform, Hours, Sessions, Avg, Last Played, Trophies, %
        $crawler->filter('table tbody tr')->each(function (Crawler $row) use (&$games) {
            $cells = $row->filter('td');

            if ($cells->count() < 8) {
                return;
            }

            $imageUrl = null;
            $imgNode = $cells->eq(1)->filter('img');
            if ($imgNode->count()) {
                $src = $imgNode->attr('data-src') ?? $imgNode->attr('src');
                if ($src) {
                    $imageUrl = str_starts_with($src, 'http') ? $src : self::BASE_URL.$src;
                }
            }

            $name = trim($cells->eq(2)->text(''));
            $platform = trim($cells->eq(3)->text(''));
            $hours = $this->parsePlaytime(trim($cells->eq(4)->text('')));
            $sessions = (int) preg_replace('/[^0-9]/', '', $cells->eq(5)->text('0'));
            $avgMinutes = $this->parseAvgSession(trim($cells->eq(6)->text('')));
            $lastPlayedAt = $this->parseLastPlayed(trim($cells->eq(7)->text('')));

            $trophies = null;
            $completion = null;
            if ($cells->count() >= 10) {
                $trophies = (int) preg_replace('/[^0-9]/', '', $cells->eq(8)->text('')) ?: null;
                $completion = (float) preg_replace('/[^0-9.]/', '', $cells->eq(9)->text('')) ?: null;
            }

            $psnUrl = null;
            $linkNode = $cells->eq(2)->filter('a');
            if ($linkNode->count()) {
                $psnUrl = self::BASE_URL.$linkNode->attr('href');
            }

            if ($name) {
                $games[] = compact('name', 'platform', 'imageUrl', 'hours', 'sessions', 'avgMinutes', 'lastPlayedAt', 'trophies', 'completion', 'psnUrl');
            }
        });

        return $games;
    }

    public function syncGames(string $username): int
    {
        $games = $this->scrapeGames($username);
        $synced = 0;

        foreach ($games as $game) {
            $existing = PlayStationGame::where('name', $game['name'])
                ->where('platform', $game['platform'])
                ->first();

            if ($existing?->exclude_from_sync) {
                continue;
            }

            PlayStationGame::updateOrCreate(
                ['name' => $game['name'], 'platform' => $game['platform']],
                [
                    'image_url' => $game['imageUrl'],
                    'hours' => $game['hours'],
                    'sessions' => $game['sessions'],
                    'avg_session_minutes' => $game['avgMinutes'] ?? 0,
                    'last_played_at' => $game['lastPlayedAt'],
                    'trophies' => $game['trophies'] ?? 0,
                    'completion_percentage' => $game['completion'] ?? 0,
                    'psn_url' => $game['psnUrl'],
                ]
            );

            $synced++;
        }

        return $synced;
    }

    public function syncSessions(string $username, bool $allPages = false): int
    {
        $gameMap = PlayStationGame::all()
            ->keyBy(fn (PlayStationGame $g) => $g->name.'|'.$g->platform)
            ->map(fn (PlayStationGame $g) => $g->id)
            ->toArray();

        $latestSession = PlayStationSession::orderByDesc('started_at')->first();
        $latestSessionDate = $latestSession?->started_at;

        $synced = 0;
        $page = 1;
        $updatedGameIds = [];

        while (true) {
            ['sessions' => $sessions, 'hasMore' => $hasMore] = $this->scrapeSessionsPage($username, $page);

            if (empty($sessions)) {
                break;
            }

            $pageHasNew = false;

            foreach ($sessions as $data) {
                $key = $data['game_name'].'|'.$data['platform'];
                $gameId = $gameMap[$key] ?? null;

                if (! $gameId) {
                    $game = PlayStationGame::create([
                        'name' => $data['game_name'],
                        'platform' => $data['platform'],
                    ]);
                    $gameId = $game->id;
                    $gameMap[$key] = $gameId;
                }

                $sessionStart = Carbon::parse($data['started_at']);

                if (! $allPages && $latestSessionDate && $sessionStart->lte($latestSessionDate)) {
                    $exists = PlayStationSession::where('play_station_game_id', $gameId)
                        ->where('started_at', $data['started_at'])
                        ->exists();

                    if ($exists) {
                        continue;
                    }
                }

                try {
                    PlayStationSession::create([
                        'play_station_game_id' => $gameId,
                        'started_at' => $data['started_at'],
                        'ended_at' => $data['ended_at'],
                        'duration_minutes' => $data['duration_minutes'],
                    ]);
                    $synced++;
                    $pageHasNew = true;
                    $updatedGameIds[$gameId] = true;
                } catch (\Illuminate\Database\QueryException) {
                    // duplicate, skip
                }
            }

            if (! $hasMore || (! $allPages && ! $pageHasNew && $latestSessionDate)) {
                break;
            }

            $page++;

            if ($page > 50) {
                break;
            }
        }

        foreach (array_keys($updatedGameIds) as $gameId) {
            $max = PlayStationSession::where('play_station_game_id', $gameId)->max('started_at');
            if ($max) {
                PlayStationGame::where('id', $gameId)->update([
                    'last_played_at' => Carbon::parse($max)->toDateString(),
                ]);
            }
        }

        return $synced;
    }

    private function scrapeSessionsPage(string $username, int $page): array
    {
        $response = Http::withoutVerifying()
            ->withHeaders($this->headers())
            ->get(self::BASE_URL."/profile/{$username}/playtimes?page={$page}");

        if (! $response->successful()) {
            return ['sessions' => [], 'hasMore' => false];
        }

        $html = $response->body();

        if (str_contains($html, 'Your PSN-Name') && str_contains($html, 'Your Code')) {
            return ['sessions' => [], 'hasMore' => false];
        }

        $crawler = new Crawler($html);
        $sessions = [];

        // Column structure: Empty, Game, Platform, Duration, Start, End, Edit
        $crawler->filter('table tbody tr')->each(function (Crawler $row) use (&$sessions) {
            $cells = $row->filter('td');

            if ($cells->count() < 6) {
                return;
            }

            $gameName = trim($cells->eq(1)->text(''));
            $platform = trim($cells->eq(2)->text(''));
            $durationText = trim($cells->eq(3)->text(''));
            $startText = trim($cells->eq(4)->text(''));
            $endText = trim($cells->eq(5)->text(''));

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
        });

        $hasMore = $crawler->filter('a[rel="next"]')->count() > 0;

        return ['sessions' => $sessions, 'hasMore' => $hasMore];
    }

    private function headers(): array
    {
        return $this->cookie
            ? ['Cookie' => '_my_app_session='.$this->cookie]
            : [];
    }

    private function parsePlaytime(string $text): float
    {
        if ($text === '' || $text === '-') {
            return 0.0;
        }

        // Remove thousand separators
        $text = str_replace(',', '', trim($text));

        $hours = 0.0;
        $matched = false;

        // Days: "4d" or "4 d"
        if (preg_match('/(\d+)\s*d/i', $text, $m)) {
            $hours += (float) $m[1] * 24;
            $matched = true;
        }

        // Hours: "586h" or "586 h"
        if (preg_match('/(\d+)\s*h/i', $text, $m)) {
            $hours += (float) $m[1];
            $matched = true;
        }

        // Minutes: "30m" or "30 m"
        if (preg_match('/(\d+)\s*m/i', $text, $m)) {
            $hours += (float) $m[1] / 60;
            $matched = true;
        }

        // Plain number with no unit — assume hours
        if (! $matched && preg_match('/^[\d.]+$/', $text)) {
            $hours = (float) $text;
        }

        return round($hours, 1);
    }

    private function parseAvgSession(string $text): ?int
    {
        if ($text === '' || $text === '-') {
            return null;
        }

        $text = str_replace(',', '', trim($text));
        $minutes = 0;

        if (preg_match('/(\d+)\s*h/i', $text, $m)) {
            $minutes += (int) $m[1] * 60;
        }
        if (preg_match('/(\d+)\s*m/i', $text, $m)) {
            $minutes += (int) $m[1];
        }

        // Plain number — assume minutes
        if ($minutes === 0 && preg_match('/^[\d.]+$/', $text)) {
            $minutes = (int) $text;
        }

        return $minutes > 0 ? $minutes : null;
    }

    private function parseLastPlayed(string $text): ?string
    {
        if ($text === '' || $text === '-') {
            return null;
        }

        try {
            if (stripos($text, 'today') !== false) {
                return Carbon::today()->toDateString();
            }
            if (stripos($text, 'yesterday') !== false) {
                return Carbon::yesterday()->toDateString();
            }

            return Carbon::parse($text)->toDateString();
        } catch (\Exception) {
            return null;
        }
    }

    private function parseSessionDateTime(string $text): ?string
    {
        if ($text === '' || $text === '-') {
            return null;
        }

        try {
            return Carbon::parse($text)->toDateTimeString();
        } catch (\Exception) {
            return null;
        }
    }

    private function parseSessionDuration(string $text): int
    {
        if ($text === '' || $text === '-') {
            return 0;
        }

        $minutes = 0;

        if (preg_match('/(\d+):(\d+)\s*hours?/i', $text, $m)) {
            return (int) $m[1] * 60 + (int) $m[2];
        }

        if (preg_match('/(\d+)\s*hours?/i', $text, $m)) {
            $minutes += (int) $m[1] * 60;
        }
        if (preg_match('/(\d+)\s*minutes?/i', $text, $m)) {
            $minutes += (int) $m[1];
        }
        if (preg_match('/(\d+)\s*h\b/i', $text, $m)) {
            $minutes += (int) $m[1] * 60;
        }
        if (preg_match('/(\d+)\s*m\b/i', $text, $m)) {
            $minutes += (int) $m[1];
        }

        return $minutes;
    }
}

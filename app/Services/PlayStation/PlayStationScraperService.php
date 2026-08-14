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
    private string $baseUrl = 'https://ps-timetracker.com';

    private ?string $cookie = null;

    public function setSessionCookie(string $cookie): void
    {
        $this->cookie = $cookie;
    }

    public function scrapeGames(string $username): array
    {
        $response = Http::withoutVerifying()
            ->withHeaders(['Cookie' => $this->cookie ?? ''])
            ->get("{$this->baseUrl}/user/{$username}");

        if (! $response->successful()) {
            return [];
        }

        $crawler = new Crawler($response->body());
        $games = [];

        $crawler->filter('table tbody tr')->each(function (Crawler $row) use (&$games) {
            $cells = $row->filter('td');

            if ($cells->count() < 4) {
                return;
            }

            $name = trim($cells->eq(0)->text(''));
            $platform = trim($cells->eq(1)->text(''));
            $hours = (float) preg_replace('/[^0-9.]/', '', $cells->eq(2)->text('0'));
            $sessions = (int) preg_replace('/[^0-9]/', '', $cells->eq(3)->text('0'));

            $imageUrl = null;

            if ($cells->eq(0)->filter('img')->count() > 0) {
                $imageUrl = $cells->eq(0)->filter('img')->attr('src');
            }

            if ($name) {
                $games[] = compact('name', 'platform', 'hours', 'sessions', 'imageUrl');
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
                    'hours' => $game['hours'],
                    'sessions' => $game['sessions'],
                    'image_url' => $game['imageUrl'],
                ]
            );

            $synced++;
        }

        return $synced;
    }

    public function scrapeSessions(string $username, string $gameName, string $platform): array
    {
        $slug = urlencode($gameName);
        $response = Http::withoutVerifying()
            ->withHeaders(['Cookie' => $this->cookie ?? ''])
            ->get("{$this->baseUrl}/user/{$username}/game/{$slug}");

        if (! $response->successful()) {
            return [];
        }

        $crawler = new Crawler($response->body());
        $sessions = [];

        $crawler->filter('table tbody tr')->each(function (Crawler $row) use (&$sessions) {
            $cells = $row->filter('td');

            if ($cells->count() < 2) {
                return;
            }

            $dateText = trim($cells->eq(0)->text(''));
            $durationText = trim($cells->eq(1)->text(''));

            $minutes = 0;

            if (preg_match('/(\d+)h/', $durationText, $m)) {
                $minutes += (int) $m[1] * 60;
            }

            if (preg_match('/(\d+)m/', $durationText, $m)) {
                $minutes += (int) $m[1];
            }

            try {
                $startedAt = Carbon::parse($dateText);
                $sessions[] = [
                    'started_at' => $startedAt,
                    'ended_at' => $minutes > 0 ? $startedAt->copy()->addMinutes($minutes) : null,
                    'duration_minutes' => $minutes,
                ];
            } catch (\Exception) {
                // skip unparseable rows
            }
        });

        return $sessions;
    }

    public function syncSessions(string $username, bool $allPages = false): int
    {
        $synced = 0;
        $games = PlayStationGame::where('exclude_from_sync', false)->get();

        foreach ($games as $game) {
            $sessions = $this->scrapeSessions($username, $game->name, $game->platform);

            foreach ($sessions as $session) {
                $created = PlayStationSession::firstOrCreate(
                    [
                        'play_station_game_id' => $game->id,
                        'started_at' => $session['started_at'],
                    ],
                    [
                        'ended_at' => $session['ended_at'],
                        'duration_minutes' => $session['duration_minutes'],
                    ]
                );

                if ($created->wasRecentlyCreated) {
                    $synced++;
                }
            }

            $latest = $game->sessions()->latest('started_at')->first();

            if ($latest) {
                $game->update(['last_played_at' => $latest->started_at->toDateString()]);
            }
        }

        return $synced;
    }
}

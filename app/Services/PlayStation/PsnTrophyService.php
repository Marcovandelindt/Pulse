<?php

declare(strict_types=1);

namespace App\Services\PlayStation;

use App\Models\PlayStationGame;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class PsnTrophyService
{
    private const TROPHY_BASE = 'https://m.np.playstation.com/api/trophy/v1';
    private const USER_AGENT  = 'PlayStation/23.4.0 CFNetwork/1410.0.3 Darwin/22.6.0';

    public function __construct(
        private readonly PsnAuthService $auth,
    ) {}

    public function fetchAndStoreTrophies(PlayStationGame $game): array
    {
        $titles = $this->fetchAllTrophyTitles();
        $localNorm = $this->normalizeTitle($game->name);

        // Find this game's entry in the user's trophy titles list
        $entry = $this->findTrophyTitle($titles, $game->name, $localNorm);

        if (! $entry) {
            throw new RuntimeException(
                "Could not find \"{$game->name}\" in your PSN trophy list. ".
                'The game may not have trophies or may not be played on this account.'
            );
        }

        // Save np_communication_id if not already set
        if (! $game->np_communication_id) {
            $game->np_communication_id = $entry['npCommunicationId'];
            $game->np_service_name     = $entry['npServiceName'] ?? 'trophy';
        }

        $earned  = $entry['earnedTrophies'] ?? [];
        $defined = $entry['definedTrophies'] ?? [];
        $progress = (int) ($entry['progress'] ?? 0);

        $game->trophies               = array_sum($earned);
        $game->trophy_progress        = $progress;
        $game->trophy_earned          = $earned;
        $game->trophy_defined         = $defined;
        $game->trophies_last_synced_at = now();
        $game->save();

        $totalDefined = array_sum($defined);
        $totalEarned  = array_sum($earned);

        return [
            'total'    => $totalDefined,
            'earned'   => $totalEarned,
            'progress' => $progress,
        ];
    }

    private function findTrophyTitle(array $titles, string $name, string $normalizedName): ?array
    {
        // Sort longest titles first so more specific matches win in all steps
        $collection = collect($titles)->sortByDesc(fn ($t) => strlen($t['trophyTitleName'] ?? ''));

        // 1. Exact match
        $match = $collection->first(fn ($t) => strcasecmp($t['trophyTitleName'] ?? '', $name) === 0);

        // 2. Normalized match (strips punctuation and platform suffixes)
        if (! $match) {
            $match = $collection->first(
                fn ($t) => $this->normalizeTitle($t['trophyTitleName'] ?? '') === $normalizedName
            );
        }

        // 3. Word-coverage: every word in the PSN title appears in the local name AND covers ≥70%
        //    of the local name's words. Prevents "Dying Light" from matching "Dying Light 2".
        if (! $match) {
            $localWords = array_filter(preg_split('/\s+/', strtolower($name)));
            $match = $collection->first(function ($t) use ($localWords) {
                $psnWords = array_filter(preg_split('/\s+/', strtolower($t['trophyTitleName'] ?? '')));
                if (count($psnWords) === 0) {
                    return false;
                }

                $allPresent = count(array_diff($psnWords, $localWords)) === 0;
                $coverage   = count($psnWords) / max(1, count($localWords));

                return $allPresent && $coverage >= 0.70;
            });
        }

        // 4. Local name contained in PSN title (e.g. "God of War" inside "God of War™ Remastered")
        if (! $match) {
            $match = $collection->first(
                fn ($t) => str_contains(strtolower($t['trophyTitleName'] ?? ''), strtolower($name))
            );
        }

        return $match;
    }

    private function normalizeTitle(string $title): string
    {
        $title = preg_replace('/\s*\(PlayStation[®™]?\d?\)/i', '', $title);
        $title = preg_replace('/\s*\(PS[345]\)/i', '', $title);
        $title = preg_replace('/[^\p{L}\p{N}\s]/u', '', $title);

        return strtolower(trim(preg_replace('/\s+/', ' ', $title)));
    }

    private function fetchAllTrophyTitles(): array
    {
        return Cache::remember('psn_trophy_titles', 3600, function () {
            $token  = $this->auth->getAccessToken();
            $titles = [];
            $offset = 0;
            $limit  = 800;

            do {
                $response = Http::withToken($token)
                    ->withHeaders([
                        'Accept'          => 'application/json',
                        'Accept-Language' => 'en-US',
                        'User-Agent'      => self::USER_AGENT,
                    ])
                    ->get(self::TROPHY_BASE.'/users/me/trophyTitles', [
                        'limit'  => $limit,
                        'offset' => $offset,
                    ]);

                if (! $response->successful()) {
                    throw new RuntimeException('Failed to fetch PSN trophy titles: '.$response->body());
                }

                $data    = $response->json();
                $batch   = $data['trophyTitles'] ?? [];
                $titles  = array_merge($titles, $batch);
                $total   = $data['totalItemCount'] ?? 0;
                $offset += count($batch);
            } while ($offset < $total && count($batch) > 0);

            return $titles;
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Spotify\SpotifyTrackService;
use Illuminate\Console\Command;

final class SyncSpotifyTracks extends Command
{
    protected $signature = 'spotify:sync-tracks';

    protected $description = 'Sync recently played Spotify tracks';

    public function handle(SpotifyTrackService $service): int
    {
        $result = $service->syncRecentlyPlayed();

        $this->info("Synced: {$result['synced']}, Skipped: {$result['skipped']}, Total: {$result['total']}");

        return Command::SUCCESS;
    }
}

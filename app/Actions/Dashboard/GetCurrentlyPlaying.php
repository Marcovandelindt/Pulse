<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\Track;
use App\Services\Spotify\SpotifyTrackService;
use Illuminate\Support\Facades\Log;

final class GetCurrentlyPlaying
{
    public function __construct(
        private readonly SpotifyTrackService $trackService,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function handle(): ?array
    {
        try {
            $currentlyPlaying = $this->trackService->getCurrentlyPlaying();

            if ($currentlyPlaying === null) {
                return null;
            }

            $track = Track::where('spotify_track_id', $currentlyPlaying['spotify_track_id'])->first()
                ?? $this->trackService->upsertTrack($currentlyPlaying['raw_track'], fetchDetails: false);

            $currentlyPlaying['track'] = $track->load('artists');
            $currentlyPlaying['artist_models'] = $track->artists->keyBy('spotify_artist_id');

            return $currentlyPlaying;
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch currently playing track', ['error' => $e->getMessage()]);

            return null;
        }
    }
}

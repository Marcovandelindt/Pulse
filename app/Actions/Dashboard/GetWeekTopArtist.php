<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\Artist;
use App\Models\Play;

final class GetWeekTopArtist
{
    /**
     * @return array<string, mixed>|null
     */
    public function handle(): ?array
    {
        $row = Artist::query()
            ->selectRaw('artists.id, COUNT(plays.id) as play_count')
            ->join('track_artists', 'artists.id', '=', 'track_artists.artist_id')
            ->join('tracks', 'track_artists.track_id', '=', 'tracks.id')
            ->join('plays', 'tracks.id', '=', 'plays.track_id')
            ->where('track_artists.is_primary', true)
            ->whereBetween('plays.played_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->groupBy('artists.id')
            ->orderByDesc('play_count')
            ->first();

        if ($row === null) {
            return null;
        }

        $artist = Artist::find($row->id);

        if ($artist === null) {
            return null;
        }

        $totalTracks = Play::whereBetween('played_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $topGenre = $artist->genres !== null && count($artist->genres) > 0
            ? ucfirst($artist->genres[0])
            : null;

        return [
            'artist' => $artist,
            'playCount' => (int) $row->getAttribute('play_count'),
            'totalTracks' => $totalTracks,
            'topGenre' => $topGenre,
        ];
    }
}

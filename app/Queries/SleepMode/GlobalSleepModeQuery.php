<?php

declare(strict_types=1);

namespace App\Queries\SleepMode;

use App\Models\Movie;
use App\Models\NintendoGame;
use App\Models\PlayStationGame;
use App\Models\PlayStationGameImage;
use App\Models\SleepModeTrack;
use App\Models\SteamGame;
use App\Models\SteamGameImage;
use App\Models\TvSeries;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

final class GlobalSleepModeQuery
{
    public function get(): array
    {
        $psGames = PlayStationGame::whereNotNull('image_url')
            ->get(['id', 'name', 'display_name', 'platform', 'image_url', 'hours', 'completion_percentage', 'last_played_at', 'is_favorite'])
            ->map(fn (PlayStationGame $g) => [
                'image_url' => $g->image_url,
                'title' => $g->label,
                'type' => 'game',
                'category' => 'PlayStation',
                'meta' => $g->platform,
                'hours' => round((float) $g->hours, 1),
                'completion' => round((float) $g->completion_percentage, 0),
                'last_played' => $g->last_played_at?->format('d M Y'),
                'first_date' => null,
                'has_cover' => true,
                'url' => route('playstation.show', $g),
                'weight' => $this->weight($g->is_favorite, $g->last_played_at),
            ]);

        $steamGames = SteamGame::where(fn ($q) => $q->whereNotNull('image_url')->orWhereNotNull('custom_image_url'))
            ->where('playtime_minutes', '>', 0)
            ->get(['id', 'name', 'image_url', 'custom_image_url', 'playtime_minutes', 'last_played_at', 'user_rating'])
            ->map(fn (SteamGame $g) => [
                'image_url' => $g->imageUrl,
                'title' => $g->name,
                'type' => 'game',
                'category' => 'Steam',
                'meta' => 'PC',
                'hours' => $g->playtimeHours,
                'completion' => null,
                'last_played' => $g->last_played_at?->format('d M Y'),
                'first_date' => null,
                'has_cover' => true,
                'url' => route('steam.games.show', $g),
                'weight' => $this->weight($g->user_rating >= 8, $g->last_played_at),
            ])
            ->filter(fn ($item) => $item['image_url'] !== null)
            ->values();

        $nintendoGames = NintendoGame::whereNotNull('image_url')
            ->where('total_minutes', '>', 0)
            ->get(['id', 'name', 'image_url', 'total_minutes', 'last_played_at', 'first_played_at'])
            ->map(fn (NintendoGame $g) => [
                'image_url' => $g->image_url,
                'title' => $g->name,
                'type' => 'game',
                'category' => 'Nintendo',
                'meta' => 'Switch',
                'hours' => round($g->total_minutes / 60, 1),
                'completion' => null,
                'last_played' => $g->last_played_at?->format('d M Y'),
                'first_date' => $g->first_played_at?->format('Y-m-d'),
                'has_cover' => true,
                'url' => route('nintendo.show', $g),
                'weight' => $this->weight(false, $g->last_played_at),
            ]);

        $movies = Movie::where(fn ($q) => $q->whereNotNull('backdrop_path')->orWhereNotNull('poster_path'))
            ->get(['id', 'title', 'poster_path', 'backdrop_path', 'release_date', 'genres', 'watch_count', 'last_watched_at', 'first_watched_at'])
            ->map(fn (Movie $m) => [
                'image_url' => $m->backdropUrl ?? $m->posterUrl,
                'title' => $m->title,
                'type' => 'movie',
                'category' => 'Movie',
                'meta' => $m->release_date?->format('Y'),
                'genres' => $m->genres ? implode(', ', array_slice($m->genres, 0, 3)) : null,
                'watch_count' => $m->watch_count,
                'last_watched' => $m->last_watched_at?->format('d M Y'),
                'first_date' => $m->first_watched_at?->format('Y-m-d'),
                'has_cover' => true,
                'url' => route('movies.show', $m),
                'weight' => $this->weight(($m->watch_count ?? 0) >= 2, $m->last_watched_at),
            ])
            ->filter(fn ($item) => $item['image_url'] !== null)
            ->values();

        $tvSeries = TvSeries::where(fn ($q) => $q->whereNotNull('backdrop_path')->orWhereNotNull('poster_path'))
            ->get(['id', 'name', 'poster_path', 'backdrop_path', 'first_air_date', 'genres', 'episodes_watched', 'number_of_episodes', 'completion_percentage', 'last_watched_at', 'first_watched_at', 'is_favorite'])
            ->map(fn (TvSeries $s) => [
                'image_url' => $s->backdropUrl ?? $s->posterUrl,
                'title' => $s->name,
                'type' => 'tv',
                'category' => 'TV',
                'meta' => $s->first_air_date?->format('Y'),
                'genres' => $s->genres ? implode(', ', array_slice($s->genres, 0, 3)) : null,
                'episodes' => ($s->episodes_watched ?? 0).' / '.($s->number_of_episodes ?? 0).' ep',
                'completion' => round((float) $s->completion_percentage, 0),
                'last_watched' => $s->last_watched_at?->format('d M Y'),
                'first_date' => $s->first_watched_at?->format('Y-m-d'),
                'has_cover' => true,
                'url' => route('tv.show', $s),
                'weight' => $this->weight($s->is_favorite, $s->last_watched_at),
            ])
            ->filter(fn ($item) => $item['image_url'] !== null)
            ->values();

        $sleepItems = collect()
            ->merge($psGames)
            ->merge($steamGames)
            ->merge($nintendoGames)
            ->merge($movies)
            ->merge($tvSeries)
            ->shuffle()
            ->values()
            ->all();

        $psGallery = PlayStationGameImage::with('game:id,name,display_name,platform')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($img) => [
                'image_url' => $img->url,
                'game_title' => $img->game->label,
                'caption' => $img->caption,
                'category' => 'PlayStation',
                'url' => route('playstation.show', $img->game),
            ]);

        $steamGallery = SteamGameImage::with('game:id,name')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($img) => [
                'image_url' => $img->url,
                'game_title' => $img->game->name,
                'caption' => $img->caption,
                'category' => 'Steam',
                'url' => route('steam.games.show', $img->game),
            ]);

        $galleryItems = $psGallery->merge($steamGallery)->shuffle()->values()->all();

        $audioTracks = SleepModeTrack::orderBy('sort_order')->orderBy('id')->get()
            ->map(fn ($t) => [
                'title' => $t->displayTitle(),
                'game_name' => $t->game_name,
                'url' => Storage::url($t->path),
            ])->values()->all();

        return compact('sleepItems', 'galleryItems', 'audioTracks');
    }

    private function weight(bool $isFavorite, ?Carbon $lastDate): int
    {
        $weight = 1;

        if ($lastDate?->greaterThan(now()->subDays(30))) {
            $weight = 2;
        }

        if ($isFavorite) {
            $weight++;
        }

        return min($weight, 3);
    }
}

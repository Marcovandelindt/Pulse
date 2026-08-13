<?php

declare(strict_types=1);

namespace App\Http\Controllers\Music;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\AlbumListen;
use App\Models\Artist;
use Carbon\Carbon;
use Illuminate\View\View;

final class AlbumStatsController extends Controller
{
    public function index(): View
    {
        $totalListens = AlbumListen::count();
        $totalAlbums = Album::count();
        $uniqueArtists = Artist::count();
        $averageRating = AlbumListen::whereNotNull('rating')->avg('rating');

        $topAlbums = Album::with('artist')
            ->orderByDesc('listen_count')
            ->limit(10)
            ->get();

        $topArtists = Artist::withSum('albums as total_listens', 'listen_count')
            ->orderByDesc('total_listens')
            ->limit(10)
            ->get();

        $recentHistory = $this->recentHistory();
        $listensByDay = $this->listensByDay();
        $ratingDistribution = $this->ratingDistribution();

        $firstListen = Album::whereNotNull('first_listened_at')
            ->with('artist')
            ->orderBy('first_listened_at')
            ->first();

        $lastListen = Album::whereNotNull('last_listened_at')
            ->with('artist')
            ->orderByDesc('last_listened_at')
            ->first();

        // Total hours listened (sum duration_ms * listen_count)
        $totalMs = Album::selectRaw('SUM(duration_ms * listen_count) as total_ms')->value('total_ms') ?? 0;
        $totalHours = round($totalMs / 3_600_000, 1);

        return view('pages.music.stats', compact(
            'totalListens', 'totalAlbums', 'uniqueArtists', 'averageRating',
            'topAlbums', 'topArtists', 'recentHistory', 'listensByDay',
            'ratingDistribution', 'firstListen', 'lastListen', 'totalHours',
        ));
    }

    private function recentHistory(): array
    {
        return AlbumListen::with(['album.artist'])
            ->whereNotNull('listened_at')
            ->where('year_only', false)
            ->orderByDesc('listened_at')
            ->limit(50)
            ->get()
            ->groupBy(fn ($l) => $l->listened_at->format('Y-m-d'))
            ->map(fn ($listens, $date) => [
                'date' => Carbon::parse($date)->format('d M Y'),
                'count' => $listens->count(),
                'titles' => $listens->pluck('album.name')->filter()->join(', '),
            ])
            ->values()
            ->toArray();
    }

    private function listensByDay(): array
    {
        return AlbumListen::selectRaw('DATE(listened_at) as day, COUNT(*) as count')
            ->whereNotNull('listened_at')
            ->where('year_only', false)
            ->where('listened_at', '>=', now()->subDays(90))
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => ['day' => $row->day, 'count' => $row->count])
            ->toArray();
    }

    private function ratingDistribution(): array
    {
        $dist = [];
        for ($i = 1; $i <= 10; $i++) {
            $dist[$i] = 0;
        }

        AlbumListen::whereNotNull('rating')
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->get()
            ->each(fn ($row) => $dist[(int) $row->rating] = (int) $row->count);

        return $dist;
    }
}

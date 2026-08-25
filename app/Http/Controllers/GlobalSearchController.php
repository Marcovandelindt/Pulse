<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\TvSeries;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

final class GlobalSearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $movies = Movie::query()
            ->where('title', 'like', "%{$q}%")
            ->orWhere('original_title', 'like', "%{$q}%")
            ->orderByDesc('watch_count')
            ->limit(5)
            ->get();

        $series = TvSeries::query()
            ->where('name', 'like', "%{$q}%")
            ->orWhere('name_en', 'like', "%{$q}%")
            ->orWhere('original_name', 'like', "%{$q}%")
            ->orderByDesc('watched_runtime_minutes')
            ->limit(5)
            ->get();

        $results = collect();

        foreach ($movies as $movie) {
            $results->push([
                'type'       => 'movie',
                'id'         => $movie->id,
                'title'      => $movie->title,
                'subtitle'   => $movie->release_date?->year,
                'poster_url' => $movie->poster_url ?? asset('cast-placeholder.svg'),
                'url'        => route('movies.show', $movie),
            ]);
        }

        foreach ($series as $s) {
            $results->push([
                'type'       => 'tv',
                'id'         => $s->id,
                'title'      => $s->name_en ?? $s->name,
                'subtitle'   => $s->first_air_date?->year,
                'poster_url' => $s->poster_url ?? asset('cast-placeholder.svg'),
                'url'        => route('tv.show', $s),
            ]);
        }

        return response()->json($results->take(8)->values());
    }
}

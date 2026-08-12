<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media\Tv;

use App\Http\Controllers\Controller;
use App\Models\TvSeries;
use App\Services\Tmdb\TMDBTVService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TvSearchController extends Controller
{
    public function __construct(
        private readonly TMDBTVService $tmdb,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = (string) $request->input('query', '');
        $results = $this->tmdb->search($query);

        $existingIds = TvSeries::whereIn('tmdb_id', array_column($results['results'] ?? [], 'id'))
            ->pluck('id', 'tmdb_id');

        $items = array_map(fn (array $show) => [
            'tmdb_id' => $show['id'],
            'name' => $show['name'] ?? '',
            'year' => ! empty($show['first_air_date']) ? substr($show['first_air_date'], 0, 4) : null,
            'poster_url' => $show['poster_path']
                ? config('tmdb.image_base_url').config('tmdb.poster_sizes.small').$show['poster_path']
                : null,
            'overview' => $show['overview'] ?? null,
            'rating' => $show['vote_average'] ?? null,
            'already_added' => isset($existingIds[$show['id']]),
        ], $results['results'] ?? []);

        return response()->json(['results' => $items]);
    }
}

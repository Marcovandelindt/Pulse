<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media\Movies;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Services\Tmdb\TMDBMovieService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MovieSearchController extends Controller
{
    public function __construct(
        private readonly TMDBMovieService $tmdb,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = (string) $request->input('query', '');
        $results = $this->tmdb->search($query);

        $existingIds = Movie::whereIn('tmdb_id', array_column($results['results'] ?? [], 'id'))
            ->pluck('id', 'tmdb_id');

        $items = array_map(fn (array $movie) => [
            'tmdb_id' => $movie['id'],
            'title' => $movie['title'] ?? '',
            'year' => ! empty($movie['release_date']) ? substr($movie['release_date'], 0, 4) : null,
            'poster_url' => $movie['poster_path']
                ? config('tmdb.image_base_url').config('tmdb.poster_sizes.small').$movie['poster_path']
                : null,
            'overview' => $movie['overview'] ?? null,
            'rating' => $movie['vote_average'] ?? null,
            'already_added' => isset($existingIds[$movie['id']]),
        ], $results['results'] ?? []);

        return response()->json(['results' => $items]);
    }
}

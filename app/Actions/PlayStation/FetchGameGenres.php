<?php

declare(strict_types=1);

namespace App\Actions\PlayStation;

use App\Models\PlayStationCategory;
use App\Models\PlayStationGame;
use App\Services\PlayStation\IgdbService;

final class FetchGameGenres
{
    public function __construct(
        private readonly IgdbService $igdb,
    ) {}

    public function handle(PlayStationGame $game): string
    {
        $searchName = $game->display_name ?? $game->name;
        $genres     = $this->igdb->getGenresForGame($searchName);

        if (empty($genres)) {
            return "No genres found for \"{$searchName}\" on IGDB.";
        }

        $categoryIds = collect($genres)
            ->map(fn (string $genre) => PlayStationCategory::firstOrCreate(['name' => $genre])->id)
            ->all();

        $game->categories()->syncWithoutDetaching($categoryIds);

        $count = count($genres);
        $list  = implode(', ', $genres);

        return "Added {$count} genre(s) from IGDB: {$list}.";
    }
}

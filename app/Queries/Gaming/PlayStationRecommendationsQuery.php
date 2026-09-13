<?php

declare(strict_types=1);

namespace App\Queries\Gaming;

use App\Enums\BacklogStatus;
use App\Models\PlayStationGame;
use Illuminate\Support\Collection;

final class PlayStationRecommendationsQuery
{
    /** @return array<string, mixed> */
    public function handle(): array
    {
        $liked = PlayStationGame::with('categories')
            ->where(function ($q) {
                $q->where('backlog_status', BacklogStatus::Completed->value)
                  ->orWhere('user_rating', '>=', 7.0);
            })
            ->get();

        // Frequency map: how often each category appears across liked games
        $categoryScores = $liked
            ->flatMap(fn ($g) => $g->categories->pluck('id'))
            ->countBy()
            ->sortDesc();

        $candidates = PlayStationGame::with('categories')
            ->withSum('playSessions', 'duration_minutes')
            ->where(function ($q) {
                $q->whereNull('backlog_status')
                  ->orWhereNotIn('backlog_status', [
                      BacklogStatus::Completed->value,
                      BacklogStatus::Dropped->value,
                      BacklogStatus::ContinuouslyPlaying->value,
                  ]);
            })
            ->get();

        $scored = $candidates
            ->map(function ($game) use ($categoryScores) {
                $matchingCategories = $game->categories->filter(
                    fn ($c) => $categoryScores->has($c->id)
                );

                return [
                    'game'                => $game,
                    'score'               => $matchingCategories->sum(fn ($c) => $categoryScores->get($c->id)),
                    'matching_categories' => $matchingCategories->values(),
                ];
            })
            ->sortByDesc('score')
            ->values();

        $recommended = $scored->filter(fn ($r) => $r['score'] > 0)->values();
        $unmatched   = $scored->filter(fn ($r) => $r['score'] === 0)->values();

        return compact('recommended', 'unmatched', 'liked', 'categoryScores');
    }
}

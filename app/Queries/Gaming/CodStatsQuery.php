<?php

declare(strict_types=1);

namespace App\Queries\Gaming;

use App\Enums\CodMatchStatus;
use App\Enums\CodResult;
use App\Models\CodMatch;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class CodStatsQuery
{
    /** @return array<string, mixed> */
    public function handle(): array
    {
        /** @var Collection<int, CodMatch> $matches */
        $matches = CodMatch::with(['map', 'mode', 'topWeapon'])
            ->where('status', CodMatchStatus::Confirmed)
            ->orderBy('estimated_date')
            ->orderBy('id')
            ->get();

        if ($matches->isEmpty()) {
            return ['hasData' => false];
        }

        return [
            'hasData' => true,
            'overview' => $this->overview($matches),
            'byMap' => $this->byMap($matches),
            'byMode' => $this->byMode($matches),
            'byWeapon' => $this->byWeapon($matches),
            'weeklyTrend' => $this->weeklyTrend($matches),
            'records' => $this->records($matches),
        ];
    }

    /**
     * @param  Collection<int, CodMatch>  $matches
     * @return array<string, mixed>
     */
    private function overview(Collection $matches): array
    {
        $total = $matches->count();
        $wins = $matches->filter(fn (CodMatch $m) => $m->result === CodResult::Victory)->count();
        $deaths = $matches->sum('deaths');

        return [
            'totalMatches' => $total,
            'wins' => $wins,
            'winRate' => round($wins / $total * 100, 1),
            'overallKd' => $deaths > 0
                ? round($matches->sum('kills') / $deaths, 2)
                : (float) $matches->sum('kills'),
            'avgScore' => round($matches->avg('score') ?? 0),
            'avgDamage' => round($matches->avg('damage') ?? 0),
            'totalKills' => $matches->sum('kills'),
        ];
    }

    /**
     * @param  Collection<int, CodMatch>  $matches
     * @return Collection<int, array{name: string, matches: int, wins: int, winRate: float, avgKd: float, avgDamage: float}>
     */
    private function byMap(Collection $matches): Collection
    {
        return $matches
            ->groupBy(fn (CodMatch $m) => $m->map->name)
            ->map(function (Collection $group, string $name): array {
                $total = $group->count();
                $wins = $group->filter(fn (CodMatch $m) => $m->result === CodResult::Victory)->count();
                $deaths = $group->sum('deaths');

                return [
                    'name' => $name,
                    'matches' => $total,
                    'wins' => $wins,
                    'winRate' => round($wins / $total * 100, 1),
                    'avgKd' => $deaths > 0
                        ? round($group->sum('kills') / $deaths, 2)
                        : (float) $group->sum('kills'),
                    'avgDamage' => round($group->avg('damage') ?? 0),
                ];
            })
            ->sortByDesc('matches')
            ->values();
    }

    /**
     * @param  Collection<int, CodMatch>  $matches
     * @return Collection<int, array{name: string, matches: int, wins: int, winRate: float, avgKd: float}>
     */
    private function byMode(Collection $matches): Collection
    {
        return $matches
            ->groupBy(fn (CodMatch $m) => $m->mode->name)
            ->map(function (Collection $group, string $name): array {
                $total = $group->count();
                $wins = $group->filter(fn (CodMatch $m) => $m->result === CodResult::Victory)->count();
                $deaths = $group->sum('deaths');

                return [
                    'name' => $name,
                    'matches' => $total,
                    'wins' => $wins,
                    'winRate' => round($wins / $total * 100, 1),
                    'avgKd' => $deaths > 0
                        ? round($group->sum('kills') / $deaths, 2)
                        : (float) $group->sum('kills'),
                ];
            })
            ->sortByDesc('matches')
            ->values();
    }

    /**
     * @param  Collection<int, CodMatch>  $matches
     * @return Collection<int, array{name: string, category: string, timesUsed: int, avgAccuracy: float|null, avgKd: float}>
     */
    private function byWeapon(Collection $matches): Collection
    {
        return $matches
            ->filter(fn (CodMatch $m) => $m->topWeapon !== null)
            ->groupBy(function (CodMatch $m): string {
                $weapon = $m->topWeapon;

                return $weapon !== null ? $weapon->name : '';
            })
            ->filter(fn (Collection $g, string $name) => $name !== '')
            ->map(function (Collection $group, string $name): array {
                $deaths = $group->sum('deaths');
                $withAccuracy = $group->filter(fn (CodMatch $m) => $m->weapon_accuracy !== null);
                $firstMatch = $group->first();
                $firstWeapon = $firstMatch !== null ? $firstMatch->topWeapon : null;

                return [
                    'name' => $name,
                    'category' => $firstWeapon !== null ? $firstWeapon->category : '',
                    'timesUsed' => $group->count(),
                    'avgAccuracy' => $withAccuracy->isNotEmpty()
                        ? round($withAccuracy->avg('weapon_accuracy') ?? 0, 1)
                        : null,
                    'avgKd' => $deaths > 0
                        ? round($group->sum('kills') / $deaths, 2)
                        : (float) $group->sum('kills'),
                ];
            })
            ->sortByDesc('timesUsed')
            ->values();
    }

    /**
     * Groups matches by ISO week and returns the last 8 weeks that have data.
     *
     * @param  Collection<int, CodMatch>  $matches
     * @return Collection<int, array{weekStart: Carbon, matches: int, wins: int, winRate: float, avgKd: float}>
     */
    private function weeklyTrend(Collection $matches): Collection
    {
        return $matches
            ->groupBy(fn (CodMatch $m) => $m->estimated_date->startOfWeek()->format('Y-m-d'))
            ->map(function (Collection $group, string $weekStart): array {
                $total = $group->count();
                $wins = $group->filter(fn (CodMatch $m) => $m->result === CodResult::Victory)->count();
                $deaths = $group->sum('deaths');

                return [
                    'weekStart' => Carbon::parse($weekStart),
                    'matches' => $total,
                    'wins' => $wins,
                    'winRate' => round($wins / $total * 100, 1),
                    'avgKd' => $deaths > 0
                        ? round($group->sum('kills') / $deaths, 2)
                        : (float) $group->sum('kills'),
                ];
            })
            ->sortKeysDesc()
            ->take(8)
            ->values();
    }

    /**
     * @param  Collection<int, CodMatch>  $matches
     * @return array<string, CodMatch|null>
     */
    private function records(Collection $matches): array
    {
        return [
            'highestScore' => $matches->sortByDesc('score')->first(),
            'mostKills' => $matches->sortByDesc('kills')->first(),
            'mostDamage' => $matches->sortByDesc('damage')->first(),
            'bestKd' => $matches
                ->filter(fn (CodMatch $m) => $m->deaths > 0)
                ->sortByDesc(fn (CodMatch $m) => (float) $m->kd_ratio)
                ->first(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Stats;

use App\Models\EpisodeWatch;
use App\Models\HealthEntry;
use App\Models\MovieWatch;
use App\Models\Play;
use App\Models\PlayStationSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class BuildWeekReportAction
{
    public function handle(Carbon $weekStart): array
    {
        $weekEnd      = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
        $prevStart    = $weekStart->copy()->subWeek();
        $prevEnd      = $weekEnd->copy()->subWeek();

        return [
            'week_start' => $weekStart,
            'week_end'   => $weekEnd,
            'steps'      => $this->steps($weekStart, $weekEnd, $prevStart, $prevEnd),
            'gaming'     => $this->gaming($weekStart, $weekEnd, $prevStart, $prevEnd),
            'music'      => $this->music($weekStart, $weekEnd, $prevStart, $prevEnd),
            'media'      => $this->media($weekStart, $weekEnd, $prevStart, $prevEnd),
            'records'    => $this->recordsBroken($weekStart, $weekEnd),
        ];
    }

    private function steps(Carbon $start, Carbon $end, Carbon $prevStart, Carbon $prevEnd): array
    {
        $entries = HealthEntry::query()
            ->whereNotNull('steps')
            ->whereBetween('date', [$start, $end])
            ->get(['steps', 'date']);

        $total   = $entries->sum('steps');
        $days    = $entries->count();
        $best    = $entries->sortByDesc('steps')->first();
        $prevTotal = (int) HealthEntry::query()
            ->whereNotNull('steps')
            ->whereBetween('date', [$prevStart, $prevEnd])
            ->sum('steps');

        return [
            'total'    => $total,
            'avg'      => $days > 0 ? (int) round($total / $days) : 0,
            'days'     => $days,
            'best'     => $best ? ['value' => (int) $best->steps, 'date' => Carbon::parse($best->date)] : null,
            'vs_prev'  => $this->pctChange($prevTotal, $total),
        ];
    }

    private function gaming(Carbon $start, Carbon $end, Carbon $prevStart, Carbon $prevEnd): array
    {
        $sessions = PlayStationSession::query()
            ->with('game:id,display_name,name')
            ->whereBetween('started_at', [$start, $end])
            ->get(['play_station_game_id', 'duration_minutes', 'started_at']);

        $total    = $sessions->sum('duration_minutes');
        $count    = $sessions->count();
        $topGame  = $sessions
            ->groupBy('play_station_game_id')
            ->map(fn ($g) => ['name' => $g->first()->game?->display_name ?? $g->first()->game?->name, 'minutes' => $g->sum('duration_minutes')])
            ->sortByDesc('minutes')
            ->first();

        $prevTotal = (int) PlayStationSession::query()
            ->whereBetween('started_at', [$prevStart, $prevEnd])
            ->sum('duration_minutes');

        return [
            'total_minutes' => (int) $total,
            'sessions'      => $count,
            'top_game'      => $topGame ? $topGame['name'] : null,
            'vs_prev'       => $this->pctChange($prevTotal, (int) $total),
        ];
    }

    private function music(Carbon $start, Carbon $end, Carbon $prevStart, Carbon $prevEnd): array
    {
        $plays = Play::query()
            ->with('track.artists:id,name')
            ->whereNotNull('played_at')
            ->whereBetween('played_at', [$start, $end])
            ->get(['id', 'track_id', 'played_at']);

        $total       = $plays->count();
        $uniqueTracks = $plays->unique('track_id')->count();

        $topArtist = $plays
            ->flatMap(fn ($play) => $play->track?->artists ?? collect())
            ->groupBy('id')
            ->map(fn ($g) => ['name' => $g->first()->name, 'plays' => $g->count()])
            ->sortByDesc('plays')
            ->first();

        $prevTotal = Play::query()
            ->whereNotNull('played_at')
            ->whereBetween('played_at', [$prevStart, $prevEnd])
            ->count();

        return [
            'total'        => $total,
            'unique_tracks' => $uniqueTracks,
            'top_artist'   => $topArtist ? $topArtist['name'] : null,
            'vs_prev'      => $this->pctChange($prevTotal, $total),
        ];
    }

    private function media(Carbon $start, Carbon $end, Carbon $prevStart, Carbon $prevEnd): array
    {
        $episodes = EpisodeWatch::query()
            ->whereNotNull('watched_at')
            ->whereBetween('watched_at', [$start, $end])
            ->count();

        $films = MovieWatch::query()
            ->whereNotNull('watched_at')
            ->whereBetween('watched_at', [$start, $end])
            ->count();

        $prevEpisodes = EpisodeWatch::query()
            ->whereNotNull('watched_at')
            ->whereBetween('watched_at', [$prevStart, $prevEnd])
            ->count();

        $prevFilms = MovieWatch::query()
            ->whereNotNull('watched_at')
            ->whereBetween('watched_at', [$prevStart, $prevEnd])
            ->count();

        return [
            'episodes' => $episodes,
            'films'    => $films,
            'vs_prev'  => $this->pctChange($prevEpisodes + $prevFilms, $episodes + $films),
        ];
    }

    /** @return list<array{domain: string, label: string, value: string}> */
    private function recordsBroken(Carbon $start, Carbon $end): array
    {
        $records = [];

        // Steps: best day this week vs all-time best day before this week
        $bestThisWeek = (int) HealthEntry::query()
            ->whereNotNull('steps')
            ->whereBetween('date', [$start, $end])
            ->max('steps');

        $bestBefore = (int) HealthEntry::query()
            ->whereNotNull('steps')
            ->where('date', '<', $start)
            ->max('steps');

        if ($bestThisWeek > 0 && $bestThisWeek > $bestBefore) {
            $records[] = [
                'domain' => 'steps',
                'label'  => 'Most steps in a day',
                'value'  => number_format($bestThisWeek) . ' steps',
                'prev'   => $bestBefore > 0 ? 'Previous: ' . number_format($bestBefore) : 'First record',
            ];
        }

        // Gaming: longest single session this week vs all-time before
        $longestThisWeek = (int) PlayStationSession::query()
            ->whereBetween('started_at', [$start, $end])
            ->max('duration_minutes');

        $longestBefore = (int) PlayStationSession::query()
            ->where('started_at', '<', $start)
            ->max('duration_minutes');

        if ($longestThisWeek > 0 && $longestThisWeek > $longestBefore) {
            $records[] = [
                'domain' => 'gaming',
                'label'  => 'Longest gaming session',
                'value'  => $this->formatMinutes($longestThisWeek),
                'prev'   => $longestBefore > 0 ? 'Previous: ' . $this->formatMinutes($longestBefore) : 'First record',
            ];
        }

        // Music: most tracks in a day this week vs all-time before
        $bestMusicDay = Play::query()
            ->whereNotNull('played_at')
            ->whereBetween('played_at', [$start, $end])
            ->select(DB::raw('DATE(played_at) as day'), DB::raw('COUNT(*) as total'))
            ->groupBy('day')
            ->orderByDesc('total')
            ->value('total');

        $bestMusicBefore = Play::query()
            ->whereNotNull('played_at')
            ->where('played_at', '<', $start)
            ->select(DB::raw('DATE(played_at) as day'), DB::raw('COUNT(*) as total'))
            ->groupBy('day')
            ->orderByDesc('total')
            ->value('total');

        if ($bestMusicDay > 0 && $bestMusicDay > $bestMusicBefore) {
            $records[] = [
                'domain' => 'music',
                'label'  => 'Most tracks in a day',
                'value'  => number_format((int) $bestMusicDay) . ' tracks',
                'prev'   => $bestMusicBefore > 0 ? 'Previous: ' . number_format((int) $bestMusicBefore) : 'First record',
            ];
        }

        return $records;
    }

    private function pctChange(int $prev, int $current): ?float
    {
        if ($prev === 0) return null;
        return round(($current - $prev) / $prev * 100, 1);
    }

    private function formatMinutes(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        if ($h > 0 && $m > 0) return "{$h}h {$m}m";
        return $h > 0 ? "{$h}h" : "{$m}m";
    }
}

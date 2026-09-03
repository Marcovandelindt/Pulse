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

final class BuildRecordsAction
{
    public function steps(): ?array
    {
        $entry = HealthEntry::query()
            ->whereNotNull('steps')
            ->orderByDesc('steps')
            ->first(['steps', 'date']);

        if (! $entry) return null;

        return [
            'value'    => number_format($entry->steps),
            'subtitle' => Carbon::parse($entry->date)->format('M j, Y'),
        ];
    }

    public function longestGamingSession(): ?array
    {
        $session = PlayStationSession::query()
            ->orderByDesc('duration_minutes')
            ->first(['duration_minutes', 'started_at']);

        if (! $session) return null;

        return [
            'value'    => $this->formatMinutes($session->duration_minutes),
            'subtitle' => Carbon::parse($session->started_at)->format('M j, Y'),
        ];
    }

    public function mostGamingInADay(): ?array
    {
        $row = PlayStationSession::query()
            ->select(DB::raw('DATE(started_at) as day'), DB::raw('SUM(duration_minutes) as total'))
            ->groupBy('day')
            ->orderByDesc('total')
            ->first();

        if (! $row) return null;

        return [
            'value'    => $this->formatMinutes((int) $row->total),
            'subtitle' => Carbon::parse($row->day)->format('M j, Y'),
        ];
    }

    public function mostTracksInADay(): ?array
    {
        $row = Play::query()
            ->whereNotNull('played_at')
            ->select(DB::raw('DATE(played_at) as day'), DB::raw('COUNT(*) as total'))
            ->groupBy('day')
            ->orderByDesc('total')
            ->first();

        if (! $row) return null;

        return [
            'value'    => number_format((int) $row->total) . ' tracks',
            'subtitle' => Carbon::parse($row->day)->format('M j, Y'),
        ];
    }

    public function mostMediaInADay(): ?array
    {
        $episodes = EpisodeWatch::query()
            ->whereNotNull('watched_at')
            ->select(DB::raw('DATE(watched_at) as day'), DB::raw('COUNT(*) as total'))
            ->groupBy('day')
            ->pluck('total', 'day')
            ->map(fn ($v) => (int) $v);

        $movies = MovieWatch::query()
            ->whereNotNull('watched_at')
            ->select(DB::raw('DATE(watched_at) as day'), DB::raw('COUNT(*) as total'))
            ->groupBy('day')
            ->pluck('total', 'day')
            ->map(fn ($v) => (int) $v);

        $combined = $episodes->mergeRecursive($movies)
            ->map(fn ($v) => is_array($v) ? array_sum($v) : $v);

        if ($combined->isEmpty()) return null;

        $day   = $combined->sortDesc()->keys()->first();
        $total = $combined->max();

        return [
            'value'    => number_format($total) . ' ' . ($total === 1 ? 'item' : 'items'),
            'subtitle' => Carbon::parse($day)->format('M j, Y'),
        ];
    }

    private function formatMinutes(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        if ($h > 0 && $m > 0) return "{$h}h {$m}m";
        return $h > 0 ? "{$h}h" : "{$m}m";
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\EpisodeWatch;
use App\Models\HealthEntry;
use App\Models\HealthSleep;
use App\Models\MovieWatch;
use App\Models\NintendoDailyRecord;
use App\Models\Play;
use App\Models\PlayStationSession;
use Illuminate\Support\Carbon;

final class BuildDayView
{
    /**
     * @return array<string, mixed>
     */
    public function handle(Carbon $date): array
    {
        $health = HealthEntry::whereDate('date', $date)->first();
        $sleep = HealthSleep::whereDate('date', $date)->first();

        $sessions = PlayStationSession::with('game')
            ->whereDate('started_at', $date)
            ->orderBy('started_at')
            ->get();

        $nintendoRecords = NintendoDailyRecord::with('game')
            ->whereDate('date', $date)
            ->orderByDesc('minutes_played')
            ->get();

        $plays = Play::with(['track.artists', 'track.album'])
            ->whereDate('played_at', $date)
            ->orderBy('played_at')
            ->get();

        $movies = MovieWatch::with('movie')
            ->whereDate('watched_at', $date)
            ->whereNotNull('watched_at')
            ->get();

        $episodes = EpisodeWatch::with(['episode.season.series'])
            ->whereDate('watched_at', $date)
            ->whereNotNull('watched_at')
            ->orderBy('watched_at')
            ->get();

        $psnMinutes = (int) $sessions->sum('duration_minutes');
        $nintendoMinutes = (int) $nintendoRecords->sum('minutes_played');
        $totalGaming = $psnMinutes + $nintendoMinutes;

        $movieMinutes = (int) $movies->sum(fn (MovieWatch $w) => $w->movie->runtime ?? 0);
        $episodeMinutes = (int) $episodes->sum(fn (EpisodeWatch $w) => $w->episode->runtime ?? 0);
        $totalWatchTime = $movieMinutes + $episodeMinutes;

        $hasActivity = $health !== null
            || $sleep !== null
            || $sessions->isNotEmpty()
            || $nintendoRecords->isNotEmpty()
            || $plays->isNotEmpty()
            || $movies->isNotEmpty()
            || $episodes->isNotEmpty();

        return [
            'date' => $date,
            'health' => $health,
            'sleep' => $sleep,
            'sessions' => $sessions,
            'nintendoRecords' => $nintendoRecords,
            'plays' => $plays,
            'movies' => $movies,
            'episodesBySeries' => $episodes->groupBy(fn (EpisodeWatch $w) => $w->episode->season->series->id),
            'psnMinutes' => $psnMinutes,
            'nintendoMinutes' => $nintendoMinutes,
            'gamingFormatted' => $this->formatMinutes($totalGaming),
            'watchTimeFormatted' => $this->formatMinutes($totalWatchTime),
            'hasActivity' => $hasActivity,
        ];
    }

    private function formatMinutes(int $minutes): ?string
    {
        if ($minutes === 0) {
            return null;
        }

        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        if ($h > 0 && $m > 0) {
            return "{$h}h {$m}m";
        }

        return $h > 0 ? "{$h}h" : "{$m}m";
    }
}

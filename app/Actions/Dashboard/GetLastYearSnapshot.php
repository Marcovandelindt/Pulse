<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\EpisodeWatch;
use App\Models\HealthEntry;
use App\Models\HealthSleep;
use App\Models\MovieWatch;
use App\Models\Play;
use App\Models\PlayStationSession;

final class GetLastYearSnapshot
{
    /**
     * @return array<string, mixed>|null
     */
    public function handle(): ?array
    {
        $date = today()->subYear();

        $steps = (int) (HealthEntry::whereDate('date', $date)->value('steps') ?? 0);
        $sleep = HealthSleep::whereDate('date', $date)->first();
        $plays = Play::with(['track.artists'])->whereDate('played_at', $date)->orderBy('played_at')->get();
        $sessions = PlayStationSession::with('game')->whereDate('started_at', $date)->orderBy('started_at')->get();
        $episodes = EpisodeWatch::with(['episode.season.series'])
            ->whereDate('watched_at', $date)
            ->whereNotNull('watched_at')
            ->orderBy('watched_at')
            ->get();
        $movieWatched = MovieWatch::with('movie')->whereDate('watched_at', $date)->whereNotNull('watched_at')->first();

        $trackCount = $plays->count();
        $gamingMinutes = (int) $sessions->sum('duration_minutes');
        $episodeCount = $episodes->count();

        if ($steps === 0 && $sleep === null && $trackCount === 0 && $gamingMinutes === 0 && $episodeCount === 0 && $movieWatched === null) {
            return null;
        }

        return [
            'date' => $date,
            'steps' => $steps > 0 ? number_format($steps) : null,
            'sleep' => $sleep ? $sleep->formattedMinutes($sleep->total_sleep_minutes) : null,
            'tracks' => $trackCount > 0 ? $trackCount : null,
            'trackList' => $plays->map(fn (Play $p) => [
                'time' => $p->played_at->format('H:i'),
                'title' => $p->track->title,
                'artist' => $p->track->artists->first()->name ?? '',
                'url' => route('music.tracks.show', $p->track),
            ])->all(),
            'gaming' => $gamingMinutes > 0 ? $this->formatMinutes($gamingMinutes) : null,
            'sessions' => $sessions->map(fn (PlayStationSession $s) => [
                'game' => $s->game->label,
                'imageUrl' => $s->game->image_url,
                'start' => $s->started_at->format('H:i'),
                'end' => $s->end_time->format('H:i'),
                'duration' => $s->formatted_duration,
                'url' => route('playstation.show', $s->game),
            ])->all(),
            'episodes' => $episodeCount > 0 ? $episodeCount : null,
            'episodeList' => $episodes->map(fn (EpisodeWatch $w) => [
                'time' => ($w->watched_at ?? throw new \RuntimeException('watched_at cannot be null after whereNotNull filter'))->format('H:i'),
                'series' => $w->episode->season->series->name,
                'episode' => 'S'.$w->episode->season->season_number.'E'.$w->episode->episode_number.' — '.$w->episode->name,
                'url' => route('tv.show', $w->episode->season->series),
            ])->all(),
            'movieWatched' => $movieWatched !== null,
            'movieTitle' => $movieWatched?->movie->title,
            'movieUrl' => $movieWatched !== null ? route('movies.show', $movieWatched->movie) : null,
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

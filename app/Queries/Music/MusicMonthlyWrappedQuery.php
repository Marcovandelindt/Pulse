<?php

declare(strict_types=1);

namespace App\Queries\Music;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Play;
use App\Models\Track;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class MusicMonthlyWrappedQuery
{
    private const array MONTH_NAMES = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    /** @return array<string, mixed> */
    public function handle(int $year, int $month): array
    {
        $plays = Play::query()
            ->whereYear('played_at', $year)
            ->whereMonth('played_at', $month)
            ->toBase()
            ->get(['id', 'track_id', 'played_at']);

        if ($plays->isEmpty()) {
            return [
                'hasData' => false,
                'year' => $year,
                'month' => $month,
                'monthName' => self::MONTH_NAMES[$month],
            ];
        }

        $totalPlays = $plays->count();
        $uniqueTracks = $plays->pluck('track_id')->unique()->count();

        $totalMinutes = (int) (
            Play::query()
                ->whereYear('played_at', $year)
                ->whereMonth('played_at', $month)
                ->join('tracks', 'plays.track_id', '=', 'tracks.id')
                ->toBase()
                ->sum('tracks.duration_ms') / 60000
        );

        $uniqueArtists = (int) Play::query()
            ->whereYear('played_at', $year)
            ->whereMonth('played_at', $month)
            ->join('tracks', 'plays.track_id', '=', 'tracks.id')
            ->join('track_artists', 'tracks.id', '=', 'track_artists.track_id')
            ->where('track_artists.is_primary', true)
            ->toBase()
            ->distinct()
            ->count('track_artists.artist_id');

        $uniqueDays = $plays
            ->map(fn (\stdClass $p) => Carbon::parse((string) $p->played_at)->format('Y-m-d'))
            ->unique()
            ->count();

        $avgPlaysPerDay = $uniqueDays > 0 ? round($totalPlays / $uniqueDays, 1) : 0;

        $prev = Carbon::parse(sprintf('%d-%02d-01', $year, $month))->subMonth();
        $prevPlays = Play::query()
            ->whereYear('played_at', $prev->year)
            ->whereMonth('played_at', $prev->month)
            ->toBase()
            ->count();
        $vsPrevMonth = $prevPlays > 0
            ? round((($totalPlays - $prevPlays) / $prevPlays) * 100, 1)
            : null;

        return [
            'hasData' => true,
            'year' => $year,
            'month' => $month,
            'monthName' => self::MONTH_NAMES[$month],
            'totalPlays' => $totalPlays,
            'totalMinutes' => $totalMinutes,
            'uniqueTracks' => $uniqueTracks,
            'uniqueArtists' => $uniqueArtists,
            'daysPlayed' => $uniqueDays,
            'avgPlaysPerDay' => $avgPlaysPerDay,
            'vsPrevMonth' => $vsPrevMonth,
            'topTracks' => $this->topTracks($year, $month),
            'topArtists' => $this->topArtists($year, $month),
            'topAlbums' => $this->topAlbums($year, $month),
            'timeOfDay' => $this->timeOfDay($plays),
            'weekdayBreakdown' => $this->weekdayBreakdown($plays),
            'weekdayVsWeekend' => $this->weekdayVsWeekend($plays),
            'weeklyChart' => $this->weeklyChart($plays, $year, $month),
            'busiestDay' => $this->busiestDay($plays),
            'mostRepeatedTrack' => $this->mostRepeatedTrack($plays),
            'longestStreak' => $this->longestStreak($plays),
            'newDiscoveries' => $this->newDiscoveries($year, $month),
            'personalityArchetype' => $this->personalityArchetype($plays, $totalPlays),
            'calendarHeatmap' => $this->calendarHeatmap($plays, $year, $month),
            'firstPlay' => $this->boundaryPlay($year, $month, 'first'),
            'lastPlay' => $this->boundaryPlay($year, $month, 'last'),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function availableMonths(): array
    {
        return Play::query()
            ->toBase()
            ->selectRaw('YEAR(played_at) as year, MONTH(played_at) as month')
            ->groupByRaw('YEAR(played_at), MONTH(played_at)')
            ->orderByRaw('YEAR(played_at) DESC, MONTH(played_at) DESC')
            ->get()
            ->map(function (\stdClass $row): array {
                $y = (int) $row->year;
                $m = (int) $row->month;

                return [
                    'year' => $y,
                    'month' => $m,
                    'label' => self::MONTH_NAMES[$m].' '.$y,
                    'key' => $y.'-'.str_pad((string) $m, 2, '0', STR_PAD_LEFT),
                ];
            })
            ->all();
    }

    /** @return array<int, array{id: int, title: string, artist: string, image_url: string|null, play_count: int}> */
    private function topTracks(int $year, int $month): array
    {
        $rows = Play::query()
            ->whereYear('played_at', $year)
            ->whereMonth('played_at', $month)
            ->toBase()
            ->selectRaw('track_id, COUNT(*) as play_count')
            ->groupBy('track_id')
            ->orderByDesc('play_count')
            ->limit(5)
            ->get();

        $trackIds = $rows->pluck('track_id')->map(fn ($id) => (int) $id)->all();
        $tracks = Track::with(['album', 'artists'])->whereIn('id', $trackIds)->get()->keyBy('id');

        $result = [];
        foreach ($rows as $row) {
            $track = $tracks->get((int) $row->track_id);
            $result[] = [
                'id' => (int) $row->track_id,
                'title' => $track instanceof Track ? $track->title : 'Unknown',
                'artist' => $track instanceof Track ? $track->artists_string : '',
                'image_url' => $track instanceof Track ? $track->album?->image_url : null,
                'play_count' => (int) $row->play_count,
            ];
        }

        return $result;
    }

    /** @return array<int, array{id: int, name: string, image_url: string|null, play_count: int}> */
    private function topArtists(int $year, int $month): array
    {
        return Artist::query()
            ->select('artists.id', 'artists.name', 'artists.image_url')
            ->selectRaw('COUNT(plays.id) as play_count')
            ->join('track_artists', 'artists.id', '=', 'track_artists.artist_id')
            ->join('tracks', 'track_artists.track_id', '=', 'tracks.id')
            ->join('plays', 'tracks.id', '=', 'plays.track_id')
            ->whereYear('plays.played_at', $year)
            ->whereMonth('plays.played_at', $month)
            ->where('track_artists.is_primary', true)
            ->groupBy('artists.id', 'artists.name', 'artists.image_url')
            ->orderByDesc('play_count')
            ->limit(5)
            ->toBase()
            ->get()
            ->map(fn (\stdClass $r) => [
                'id' => (int) $r->id,
                'name' => (string) $r->name,
                'image_url' => $r->image_url !== null ? (string) $r->image_url : null,
                'play_count' => (int) $r->play_count,
            ])
            ->all();
    }

    /** @return array<int, array{id: int, name: string, image_url: string|null, play_count: int}> */
    private function topAlbums(int $year, int $month): array
    {
        return Album::query()
            ->select('albums.id', 'albums.name', 'albums.image_url')
            ->selectRaw('COUNT(plays.id) as play_count')
            ->join('tracks', 'albums.id', '=', 'tracks.album_id')
            ->join('plays', 'tracks.id', '=', 'plays.track_id')
            ->whereYear('plays.played_at', $year)
            ->whereMonth('plays.played_at', $month)
            ->groupBy('albums.id', 'albums.name', 'albums.image_url')
            ->orderByDesc('play_count')
            ->limit(3)
            ->toBase()
            ->get()
            ->map(fn (\stdClass $r) => [
                'id' => (int) $r->id,
                'name' => (string) $r->name,
                'image_url' => $r->image_url !== null ? (string) $r->image_url : null,
                'play_count' => (int) $r->play_count,
            ])
            ->all();
    }

    /**
     * @param  Collection<int, \stdClass>  $plays
     * @return array<string, mixed>
     */
    private function timeOfDay(Collection $plays): array
    {
        $buckets = ['morning' => 0, 'afternoon' => 0, 'evening' => 0, 'night' => 0];

        foreach ($plays as $p) {
            $hour = (int) Carbon::parse((string) $p->played_at)->format('H');
            $key = match (true) {
                $hour >= 6 && $hour < 12 => 'morning',
                $hour >= 12 && $hour < 18 => 'afternoon',
                $hour >= 18 => 'evening',
                default => 'night',
            };
            $buckets[$key]++;
        }

        $total = array_sum($buckets) ?: 1;
        $labels = [
            'morning' => ['label' => 'Morning',    'range' => '6 – 12',  'icon' => '🌅'],
            'afternoon' => ['label' => 'Afternoon',  'range' => '12 – 18', 'icon' => '☀️'],
            'evening' => ['label' => 'Evening',    'range' => '18 – 24', 'icon' => '🌆'],
            'night' => ['label' => 'Late night', 'range' => '0 – 6',   'icon' => '🌙'],
        ];

        $result = [];
        foreach ($buckets as $key => $count) {
            $result[$key] = [
                ...$labels[$key],
                'plays' => $count,
                'percent' => (int) round(($count / $total) * 100),
            ];
        }

        return $result;
    }

    /**
     * @param  Collection<int, \stdClass>  $plays
     * @return Collection<int, array{label: string, plays: int, percent: int}>
     */
    private function weekdayBreakdown(Collection $plays): Collection
    {
        $dayNames = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
        $byDay = $plays->groupBy(fn (\stdClass $p) => (int) Carbon::parse((string) $p->played_at)->isoFormat('E'));

        $maxPlays = 1;
        foreach ($dayNames as $iso => $_) {
            $count = $byDay->get($iso)?->count() ?? 0;
            if ($count > $maxPlays) {
                $maxPlays = $count;
            }
        }

        return collect($dayNames)->map(function (string $label, int $iso) use ($byDay, $maxPlays): array {
            $count = $byDay->get($iso)?->count() ?? 0;

            return [
                'label' => $label,
                'plays' => $count,
                'percent' => (int) round(($count / $maxPlays) * 100),
            ];
        })->values();
    }

    /**
     * @param  Collection<int, \stdClass>  $plays
     * @return array<string, mixed>
     */
    private function weekdayVsWeekend(Collection $plays): array
    {
        $weekday = $plays->filter(fn (\stdClass $p) => ! in_array(Carbon::parse((string) $p->played_at)->dayOfWeek, [0, 6], true));
        $weekend = $plays->filter(fn (\stdClass $p) => in_array(Carbon::parse((string) $p->played_at)->dayOfWeek, [0, 6], true));

        $wdCount = $weekday->count();
        $weCount = $weekend->count();
        $total = ($wdCount + $weCount) ?: 1;

        return [
            'weekday' => ['plays' => $wdCount, 'percent' => (int) round(($wdCount / $total) * 100)],
            'weekend' => ['plays' => $weCount, 'percent' => (int) round(($weCount / $total) * 100)],
        ];
    }

    /**
     * @param  Collection<int, \stdClass>  $plays
     * @return Collection<int, array{label: string, range: string, plays: int, percent: int}>
     */
    private function weeklyChart(Collection $plays, int $year, int $month): Collection
    {
        $start = Carbon::parse(sprintf('%d-%02d-01', $year, $month));
        $end = $start->copy()->endOfMonth();
        $cursor = $start->copy()->startOfWeek(Carbon::MONDAY);
        $weeks = [];
        $n = 1;

        while ($cursor->lte($end)) {
            $wStart = $cursor->copy()->max($start);
            $wEnd = $cursor->copy()->addDays(6)->min($end);
            $weeks[] = ['start' => $wStart->copy(), 'end' => $wEnd->copy(), 'n' => $n, 'plays' => 0];
            $cursor->addWeek();
            $n++;
        }

        foreach ($plays as $p) {
            $date = Carbon::parse((string) $p->played_at);
            foreach ($weeks as &$w) {
                if ($date->between($w['start'], $w['end'])) {
                    $w['plays']++;
                    break;
                }
            }
        }
        unset($w);

        $playValues = array_column($weeks, 'plays');
        $max = count($playValues) > 0 ? max($playValues) : 0;
        $max = $max ?: 1;

        return collect($weeks)->map(fn (array $w) => [
            'label' => 'W'.$w['n'],
            'range' => $w['start']->format('d').'–'.$w['end']->format('d M'),
            'plays' => $w['plays'],
            'percent' => (int) round(($w['plays'] / $max) * 100),
        ]);
    }

    /**
     * @param  Collection<int, \stdClass>  $plays
     * @return array<string, mixed>|null
     */
    private function busiestDay(Collection $plays): ?array
    {
        $byDay = $plays->groupBy(fn (\stdClass $p) => Carbon::parse((string) $p->played_at)->format('Y-m-d'));

        if ($byDay->isEmpty()) {
            return null;
        }

        $best = (string) $byDay->map(fn ($g) => $g->count())->sortDesc()->keys()->first();
        $dayGroup = $byDay->get($best);
        $count = $dayGroup?->count() ?? 0;

        $topRows = Play::query()
            ->whereDate('played_at', $best)
            ->toBase()
            ->selectRaw('track_id, COUNT(*) as play_count')
            ->groupBy('track_id')
            ->orderByDesc('play_count')
            ->limit(3)
            ->get();

        $trackIds = $topRows->pluck('track_id')->map(fn ($id) => (int) $id)->all();
        $tracks = Track::with(['artists'])->whereIn('id', $trackIds)->get()->keyBy('id');

        $topTracks = $topRows->map(function (\stdClass $row) use ($tracks): array {
            $track = $tracks->get((int) $row->track_id);

            return [
                'title' => $track instanceof Track ? (string) $track->title : 'Unknown',
                'artist' => $track instanceof Track ? $track->artists_string : '',
                'count' => (int) $row->play_count,
            ];
        })->all();

        return [
            'date' => Carbon::parse($best)->format('l, d M Y'),
            'count' => $count,
            'topTracks' => $topTracks,
        ];
    }

    /**
     * @param  Collection<int, \stdClass>  $plays
     * @return array<string, mixed>|null
     */
    private function mostRepeatedTrack(Collection $plays): ?array
    {
        $byDateTrack = $plays->groupBy(
            fn (\stdClass $p) => Carbon::parse((string) $p->played_at)->format('Y-m-d').'__'.$p->track_id
        );

        if ($byDateTrack->isEmpty()) {
            return null;
        }

        $sorted = $byDateTrack->map(fn ($g) => $g->count())->sortDesc();
        $topCount = (int) ($sorted->first() ?? 0);

        if ($topCount < 2) {
            return null;
        }

        $topKey = (string) $sorted->keys()->first();
        $parts = explode('__', $topKey, 2);

        if (count($parts) !== 2) {
            return null;
        }

        [$dateStr, $trackIdStr] = $parts;

        $track = Track::with(['album', 'artists'])->find((int) $trackIdStr);

        if (! $track instanceof Track) {
            return null;
        }

        return [
            'title' => (string) $track->title,
            'artist' => $track->artists_string,
            'image_url' => $track->album?->image_url,
            'count' => $topCount,
            'date' => Carbon::parse($dateStr)->format('d M'),
        ];
    }

    /**
     * @param  Collection<int, \stdClass>  $plays
     * @return array<string, mixed>
     */
    private function longestStreak(Collection $plays): array
    {
        $dates = $plays
            ->map(fn (\stdClass $p) => Carbon::parse((string) $p->played_at)->format('Y-m-d'))
            ->unique()
            ->sort()
            ->values();

        if ($dates->isEmpty()) {
            return ['days' => 0, 'startDate' => null, 'endDate' => null];
        }

        $best = 1;
        $run = 1;
        $runStart = (string) $dates[0];
        $bestStart = (string) $dates[0];
        $bestEnd = (string) $dates[0];

        for ($i = 1; $i < $dates->count(); $i++) {
            $diff = Carbon::parse((string) $dates[$i - 1])->diffInDays(Carbon::parse((string) $dates[$i]));

            if ((int) $diff === 1) {
                $run++;
                if ($run > $best) {
                    $best = $run;
                    $bestStart = $runStart;
                    $bestEnd = (string) $dates[$i];
                }
            } else {
                $run = 1;
                $runStart = (string) $dates[$i];
            }
        }

        return [
            'days' => $best,
            'startDate' => Carbon::parse($bestStart)->format('d M'),
            'endDate' => Carbon::parse($bestEnd)->format('d M'),
        ];
    }

    /** @return array<int, array{id: int, title: string, artist: string, image_url: string|null}> */
    private function newDiscoveries(int $year, int $month): array
    {
        $start = Carbon::parse(sprintf('%d-%02d-01', $year, $month))->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();

        $newIds = Play::query()
            ->toBase()
            ->selectRaw('track_id')
            ->groupBy('track_id')
            ->havingRaw('MIN(played_at) BETWEEN ? AND ?', [$start, $end])
            ->pluck('track_id')
            ->map(fn ($id) => (int) $id);

        if ($newIds->isEmpty()) {
            return [];
        }

        $result = [];
        foreach (Track::with(['album', 'artists'])->whereIn('id', $newIds)->limit(5)->get() as $track) {
            $result[] = [
                'id' => $track->id,
                'title' => $track->title,
                'artist' => $track->artists_string,
                'image_url' => $track->album?->image_url,
            ];
        }

        return $result;
    }

    /**
     * @param  Collection<int, \stdClass>  $plays
     * @return array<int, array<string, string>>
     */
    private function personalityArchetype(Collection $plays, int $totalPlays): array
    {
        $archetypes = [];

        $nightPlays = $plays->filter(fn (\stdClass $p) => (int) Carbon::parse((string) $p->played_at)->format('H') >= 22);
        if ($totalPlays > 0 && ($nightPlays->count() / $totalPlays) >= 0.6) {
            $archetypes[] = [
                'id' => 'night_owl',
                'label' => 'Night Owl',
                'icon' => '🌙',
                'desc' => (int) round(($nightPlays->count() / $totalPlays) * 100).'% of plays after 22:00',
            ];
        }

        $morningPlays = $plays->filter(fn (\stdClass $p) => (int) Carbon::parse((string) $p->played_at)->format('H') < 9);
        if ($totalPlays > 0 && ($morningPlays->count() / $totalPlays) >= 0.5) {
            $archetypes[] = [
                'id' => 'early_bird',
                'label' => 'Early Bird',
                'icon' => '🌅',
                'desc' => (int) round(($morningPlays->count() / $totalPlays) * 100).'% of plays before 09:00',
            ];
        }

        $byDay = $plays->groupBy(fn (\stdClass $p) => Carbon::parse((string) $p->played_at)->format('Y-m-d'));
        $maxDayPlays = (int) ($byDay->map(fn ($g) => $g->count())->max() ?? 0);
        if ($maxDayPlays >= 30) {
            $archetypes[] = [
                'id' => 'binge',
                'label' => 'Binge Listener',
                'icon' => '🎧',
                'desc' => $maxDayPlays.' plays in one day',
            ];
        }

        $byDateTrack = $plays->groupBy(fn (\stdClass $p) => Carbon::parse((string) $p->played_at)->format('Y-m-d').'__'.$p->track_id);
        $maxRepeat = (int) ($byDateTrack->map(fn ($g) => $g->count())->max() ?? 0);
        if ($maxRepeat >= 5) {
            $archetypes[] = [
                'id' => 'on_repeat',
                'label' => 'On Repeat',
                'icon' => '🔁',
                'desc' => 'A track played '.$maxRepeat.'× in one day',
            ];
        }

        if (empty($archetypes)) {
            $archetypes[] = [
                'id' => 'casual',
                'label' => 'Casual Listener',
                'icon' => '🎵',
                'desc' => 'Playing at your own rhythm',
            ];
        }

        return $archetypes;
    }

    /**
     * @param  Collection<int, \stdClass>  $plays
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function calendarHeatmap(Collection $plays, int $year, int $month): array
    {
        $byDay = $plays
            ->groupBy(fn (\stdClass $p) => Carbon::parse((string) $p->played_at)->format('Y-m-d'))
            ->map(fn ($g) => $g->count());

        $maxPlays = (int) ($byDay->max() ?: 1);
        $startOfMonth = Carbon::parse(sprintf('%d-%02d-01', $year, $month))->startOfWeek(Carbon::MONDAY);
        $endOfMonth = Carbon::parse(sprintf('%d-%02d-01', $year, $month))->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $weeks = [];
        $current = $startOfMonth->copy();

        while ($current->lte($endOfMonth)) {
            $week = [];
            for ($d = 0; $d < 7; $d++) {
                $dateKey = $current->format('Y-m-d');
                $count = (int) $byDay->get($dateKey, 0);
                $inMonth = (int) $current->format('n') === $month && (int) $current->format('Y') === $year;
                $week[] = [
                    'date' => $dateKey,
                    'day' => $inMonth ? (int) $current->format('j') : null,
                    'plays' => $count,
                    'level' => $inMonth ? $this->heatmapLevel($count, $maxPlays) : -1,
                    'in_month' => $inMonth,
                ];
                $current->addDay();
            }
            $weeks[] = $week;
        }

        return $weeks;
    }

    private function heatmapLevel(int $plays, int $max): int
    {
        if ($plays === 0) {
            return 0;
        }
        $ratio = $plays / $max;

        return match (true) {
            $ratio <= 0.25 => 1,
            $ratio <= 0.5 => 2,
            $ratio <= 0.75 => 3,
            default => 4,
        };
    }

    /** @return array<string, mixed>|null */
    private function boundaryPlay(int $year, int $month, string $which): ?array
    {
        $id = Play::query()
            ->whereYear('played_at', $year)
            ->whereMonth('played_at', $month)
            ->toBase()
            ->when(
                $which === 'first',
                fn ($q) => $q->orderBy('played_at'),
                fn ($q) => $q->orderByDesc('played_at'),
            )
            ->value('id');

        if ($id === null) {
            return null;
        }

        $play = Play::with(['track.album', 'track.artists'])->find((int) $id);

        if (! $play instanceof Play) {
            return null;
        }

        return [
            'title' => (string) $play->track->title,
            'artist' => $play->track->artists_string,
            'image_url' => $play->track->album?->image_url,
            'time' => $play->played_at->format('H:i'),
            'date' => $play->played_at->format('d M Y'),
        ];
    }
}

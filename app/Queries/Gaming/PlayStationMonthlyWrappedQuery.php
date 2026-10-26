<?php

declare(strict_types=1);

namespace App\Queries\Gaming;

use App\Models\PlayStationSession;
use App\Models\PlayStationTrophy;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class PlayStationMonthlyWrappedQuery
{
    private const array MONTH_NAMES = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];

    /** @return array<string, mixed> */
    public function handle(int $year, int $month): array
    {
        $sessions = $this->sessionsForMonth($year, $month)
            ->toBase()
            ->get([
                'play_station_sessions.id',
                'play_station_sessions.play_station_game_id',
                'play_station_sessions.duration_minutes',
                'play_station_sessions.started_at',
            ]);

        if ($sessions->isEmpty()) {
            return [
                'hasData' => false,
                'year' => $year,
                'month' => $month,
                'monthName' => self::MONTH_NAMES[$month],
            ];
        }

        $totalMinutes = (int) $sessions->sum('duration_minutes');
        $totalHours = round($totalMinutes / 60, 1);
        $totalSessions = $sessions->count();
        $uniqueGames = $sessions->pluck('play_station_game_id')->unique()->count();

        $uniqueDays = $sessions
            ->map(fn (\stdClass $s) => Carbon::parse((string) $s->started_at)->format('Y-m-d'))
            ->unique()
            ->count();

        $avgSessionMinutes = $totalSessions > 0 ? (int) round($totalMinutes / $totalSessions) : 0;
        $avgHoursPerDay = $uniqueDays > 0 ? round($totalHours / $uniqueDays, 1) : 0;

        $prev = Carbon::parse(sprintf('%d-%02d-01', $year, $month))->subMonth();
        $prevMinutes = (int) $this->sessionsForMonth($prev->year, $prev->month)
            ->toBase()
            ->sum('play_station_sessions.duration_minutes');
        $vsPrevMonth = $prevMinutes > 0
            ? round((($totalMinutes - $prevMinutes) / $prevMinutes) * 100, 1)
            : null;

        $trophies = $this->trophiesForMonth($year, $month);

        return [
            'hasData' => true,
            'year' => $year,
            'month' => $month,
            'monthName' => self::MONTH_NAMES[$month],
            'totalHours' => $totalHours,
            'totalMinutes' => $totalMinutes,
            'totalSessions' => $totalSessions,
            'uniqueGames' => $uniqueGames,
            'daysPlayed' => $uniqueDays,
            'avgSessionMinutes' => $avgSessionMinutes,
            'avgSessionFormatted' => $this->formatMinutes($avgSessionMinutes),
            'avgHoursPerDay' => $avgHoursPerDay,
            'vsPrevMonth' => $vsPrevMonth,
            'topGames' => $this->topGames($year, $month, $totalMinutes),
            'timeOfDay' => $this->timeOfDay($sessions),
            'weekdayBreakdown' => $this->weekdayBreakdown($sessions),
            'weekdayVsWeekend' => $this->weekdayVsWeekend($sessions),
            'weeklyChart' => $this->weeklyChart($sessions, $year, $month),
            'busiestDay' => $this->busiestDay($sessions),
            'longestSession' => $this->longestSession($year, $month),
            'longestStreak' => $this->longestStreak($sessions),
            'trophies' => $trophies,
            'bestTrophyDay' => $this->bestTrophyDay($year, $month),
            'topTrophyGame' => $this->topTrophyGame($year, $month),
            'nightOwl' => $this->nightOwlSessions($sessions),
            'firstSession' => $this->boundarySession($year, $month, 'first'),
            'lastSession' => $this->boundarySession($year, $month, 'last'),
            'personalityArchetype' => $this->personalityArchetype($sessions, $trophies['total'], $totalHours),
            'newGames' => $this->newGamesInMonth($year, $month),
            'calendarHeatmap' => $this->calendarHeatmap($sessions, $year, $month),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function availableMonths(): array
    {
        return PlayStationSession::query()
            ->toBase()
            ->selectRaw('YEAR(started_at) as year, MONTH(started_at) as month')
            ->groupByRaw('YEAR(started_at), MONTH(started_at)')
            ->orderByRaw('YEAR(started_at) DESC, MONTH(started_at) DESC')
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

    /** @return Collection<int, array{id: int, label: string, image_url: string|null, platform: string, hours: float, minutes: int, session_count: int, share: int}> */
    private function topGames(int $year, int $month, int $totalMinutes): Collection
    {
        return $this->sessionsForMonth($year, $month)
            ->toBase()
            ->selectRaw('
                play_station_games.id,
                play_station_games.name,
                play_station_games.display_name,
                play_station_games.image_url,
                play_station_games.platform,
                SUM(play_station_sessions.duration_minutes) as total_minutes,
                COUNT(*) as session_count
            ')
            ->groupBy(
                'play_station_games.id',
                'play_station_games.name',
                'play_station_games.display_name',
                'play_station_games.image_url',
                'play_station_games.platform',
            )
            ->orderByDesc('total_minutes')
            ->limit(3)
            ->get()
            ->map(fn (\stdClass $row) => [
                'id' => (int) $row->id,
                'label' => (string) ($row->display_name ?? $row->name),
                'image_url' => $row->image_url !== null ? (string) $row->image_url : null,
                'platform' => (string) $row->platform,
                'hours' => round((float) $row->total_minutes / 60, 1),
                'minutes' => (int) $row->total_minutes,
                'session_count' => (int) $row->session_count,
                'share' => $totalMinutes > 0
                    ? (int) round(((float) $row->total_minutes / $totalMinutes) * 100)
                    : 0,
            ]);
    }

    /**
     * @param  Collection<int, \stdClass>  $sessions
     * @return array<string, mixed>
     */
    private function timeOfDay(Collection $sessions): array
    {
        $buckets = ['morning' => 0, 'afternoon' => 0, 'evening' => 0, 'night' => 0];

        foreach ($sessions as $s) {
            $hour = (int) Carbon::parse((string) $s->started_at)->format('H');
            $key = match (true) {
                $hour >= 6 && $hour < 12 => 'morning',
                $hour >= 12 && $hour < 18 => 'afternoon',
                $hour >= 18 => 'evening',
                default => 'night',
            };
            $buckets[$key] += (int) $s->duration_minutes;
        }

        $total = array_sum($buckets) ?: 1;
        $labels = [
            'morning' => ['label' => 'Morning',    'range' => '6 – 12',  'icon' => '🌅'],
            'afternoon' => ['label' => 'Afternoon',  'range' => '12 – 18', 'icon' => '☀️'],
            'evening' => ['label' => 'Evening',    'range' => '18 – 24', 'icon' => '🌆'],
            'night' => ['label' => 'Late night', 'range' => '0 – 6',   'icon' => '🌙'],
        ];

        $result = [];
        foreach ($buckets as $key => $minutes) {
            $result[$key] = [
                ...$labels[$key],
                'hours' => round($minutes / 60, 1),
                'percent' => (int) round(($minutes / $total) * 100),
            ];
        }

        return $result;
    }

    /**
     * @param  Collection<int, \stdClass>  $sessions
     * @return Collection<int, array{label: string, hours: float, count: int, percent: int}>
     */
    private function weekdayBreakdown(Collection $sessions): Collection
    {
        $dayNames = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];
        $byDay = $sessions->groupBy(fn (\stdClass $s) => (int) Carbon::parse((string) $s->started_at)->isoFormat('E'));

        $maxMinutes = 1;
        foreach ($dayNames as $iso => $_) {
            $mins = (int) ($byDay->get($iso)?->sum('duration_minutes') ?? 0);
            if ($mins > $maxMinutes) {
                $maxMinutes = $mins;
            }
        }

        return collect($dayNames)->map(function (string $label, int $iso) use ($byDay, $maxMinutes): array {
            $group = $byDay->get($iso) ?? collect();
            $minutes = (int) $group->sum('duration_minutes');

            return [
                'label' => $label,
                'hours' => round($minutes / 60, 1),
                'count' => $group->count(),
                'percent' => (int) round(($minutes / $maxMinutes) * 100),
            ];
        })->values();
    }

    /**
     * @param  Collection<int, \stdClass>  $sessions
     * @return array<string, mixed>
     */
    private function weekdayVsWeekend(Collection $sessions): array
    {
        $weekday = $sessions->filter(fn (\stdClass $s) => ! in_array(Carbon::parse((string) $s->started_at)->dayOfWeek, [0, 6], true));
        $weekend = $sessions->filter(fn (\stdClass $s) => in_array(Carbon::parse((string) $s->started_at)->dayOfWeek, [0, 6], true));

        $wdMins = (int) $weekday->sum('duration_minutes');
        $weMins = (int) $weekend->sum('duration_minutes');
        $total = ($wdMins + $weMins) ?: 1;

        return [
            'weekday' => [
                'hours' => round($wdMins / 60, 1),
                'sessions' => $weekday->count(),
                'percent' => (int) round(($wdMins / $total) * 100),
            ],
            'weekend' => [
                'hours' => round($weMins / 60, 1),
                'sessions' => $weekend->count(),
                'percent' => (int) round(($weMins / $total) * 100),
            ],
        ];
    }

    /**
     * @param  Collection<int, \stdClass>  $sessions
     * @return Collection<int, array{label: string, range: string, hours: float, minutes: int, percent: int}>
     */
    private function weeklyChart(Collection $sessions, int $year, int $month): Collection
    {
        $start = Carbon::parse(sprintf('%d-%02d-01', $year, $month));
        $end = $start->copy()->endOfMonth();
        $cursor = $start->copy()->startOfWeek(Carbon::MONDAY);
        $weeks = [];
        $n = 1;

        while ($cursor->lte($end)) {
            $wStart = $cursor->copy()->max($start);
            $wEnd = $cursor->copy()->addDays(6)->min($end);
            $weeks[] = ['start' => $wStart->copy(), 'end' => $wEnd->copy(), 'n' => $n, 'minutes' => 0];
            $cursor->addWeek();
            $n++;
        }

        foreach ($sessions as $s) {
            $date = Carbon::parse((string) $s->started_at);
            foreach ($weeks as &$w) {
                if ($date->between($w['start'], $w['end'])) {
                    $w['minutes'] += (int) $s->duration_minutes;
                    break;
                }
            }
        }
        unset($w);

        $minuteValues = array_column($weeks, 'minutes');
        $max = count($minuteValues) > 0 ? max($minuteValues) : 0;
        $max = $max ?: 1;

        return collect($weeks)->map(fn (array $w) => [
            'label' => 'W'.$w['n'],
            'range' => $w['start']->format('d').'–'.$w['end']->format('d M'),
            'hours' => round($w['minutes'] / 60, 1),
            'minutes' => $w['minutes'],
            'percent' => (int) round(($w['minutes'] / $max) * 100),
        ]);
    }

    /**
     * @param  Collection<int, \stdClass>  $sessions
     * @return array<string, mixed>|null
     */
    private function busiestDay(Collection $sessions): ?array
    {
        $byDay = $sessions->groupBy(fn (\stdClass $s) => Carbon::parse((string) $s->started_at)->format('Y-m-d'));

        if ($byDay->isEmpty()) {
            return null;
        }

        $best = (string) $byDay->map(fn ($g) => (int) $g->sum('duration_minutes'))->sortDesc()->keys()->first();
        $group = $byDay->get($best);
        $minutes = (int) ($group?->sum('duration_minutes') ?? 0);

        $daySessions = PlayStationSession::query()
            ->toBase()
            ->join('play_station_games', 'play_station_sessions.play_station_game_id', '=', 'play_station_games.id')
            ->whereDate('play_station_sessions.started_at', $best)
            ->orderBy('play_station_sessions.started_at')
            ->get([
                'play_station_sessions.started_at',
                'play_station_sessions.duration_minutes',
                'play_station_games.id as game_id',
                'play_station_games.name',
                'play_station_games.display_name',
            ])
            ->map(fn (\stdClass $s) => [
                'game' => (string) ($s->display_name ?? $s->name),
                'gameId' => (int) $s->game_id,
                'time' => Carbon::parse((string) $s->started_at)->format('H:i'),
                'duration' => $this->formatMinutes((int) $s->duration_minutes),
            ]);

        return [
            'date' => Carbon::parse($best)->format('l, d M Y'),
            'hours' => round($minutes / 60, 1),
            'sessions' => $group?->count() ?? 0,
            'formatted' => $this->formatMinutes($minutes),
            'breakdown' => $daySessions,
        ];
    }

    /** @return array<string, mixed>|null */
    private function longestSession(int $year, int $month): ?array
    {
        $id = $this->sessionsForMonth($year, $month)
            ->toBase()
            ->orderByDesc('play_station_sessions.duration_minutes')
            ->value('play_station_sessions.id');

        if ($id === null) {
            return null;
        }

        $session = PlayStationSession::with('game:id,name,display_name')->find((int) $id);

        if (! $session instanceof PlayStationSession) {
            return null;
        }

        return [
            'game' => $session->game->display_name ?? $session->game->name,
            'gameId' => $session->game->id,
            'date' => $session->started_at->format('d M Y'),
            'time' => $session->started_at->format('H:i'),
            'duration' => $this->formatMinutes($session->duration_minutes),
            'minutes' => $session->duration_minutes,
        ];
    }

    /**
     * @param  Collection<int, \stdClass>  $sessions
     * @return array<string, mixed>
     */
    private function longestStreak(Collection $sessions): array
    {
        $dates = $sessions
            ->map(fn (\stdClass $s) => Carbon::parse((string) $s->started_at)->format('Y-m-d'))
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

    /** @return array<string, int> */
    private function trophiesForMonth(int $year, int $month): array
    {
        $byType = PlayStationTrophy::query()
            ->where('is_earned', true)
            ->whereYear('earned_at', $year)
            ->whereMonth('earned_at', $month)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->toBase()
            ->get()
            ->keyBy('type')
            ->map(fn (\stdClass $r) => (int) $r->count);

        return [
            'total' => (int) $byType->sum(),
            'platinum' => (int) ($byType->get('platinum') ?? 0),
            'gold' => (int) ($byType->get('gold') ?? 0),
            'silver' => (int) ($byType->get('silver') ?? 0),
            'bronze' => (int) ($byType->get('bronze') ?? 0),
        ];
    }

    /** @return array<string, mixed>|null */
    private function bestTrophyDay(int $year, int $month): ?array
    {
        $row = PlayStationTrophy::query()
            ->where('is_earned', true)
            ->whereYear('earned_at', $year)
            ->whereMonth('earned_at', $month)
            ->whereNotNull('earned_at')
            ->toBase()
            ->selectRaw('DATE(earned_at) as day, COUNT(*) as count')
            ->groupByRaw('DATE(earned_at)')
            ->orderByDesc('count')
            ->first();

        if (! $row instanceof \stdClass) {
            return null;
        }

        return [
            'date' => Carbon::parse((string) $row->day)->format('d M Y'),
            'day' => Carbon::parse((string) $row->day)->format('l'),
            'count' => (int) $row->count,
        ];
    }

    /** @return array<string, mixed>|null */
    private function topTrophyGame(int $year, int $month): ?array
    {
        $row = PlayStationTrophy::query()
            ->where('is_earned', true)
            ->whereYear('earned_at', $year)
            ->whereMonth('earned_at', $month)
            ->whereNotNull('earned_at')
            ->join('play_station_games', 'play_station_trophies.play_station_game_id', '=', 'play_station_games.id')
            ->toBase()
            ->selectRaw('play_station_games.id, play_station_games.name, play_station_games.display_name, play_station_games.image_url, COUNT(*) as count')
            ->groupBy('play_station_games.id', 'play_station_games.name', 'play_station_games.display_name', 'play_station_games.image_url')
            ->orderByDesc('count')
            ->first();

        if (! $row instanceof \stdClass) {
            return null;
        }

        return [
            'label' => (string) ($row->display_name ?? $row->name),
            'gameId' => (int) $row->id,
            'image_url' => $row->image_url !== null ? (string) $row->image_url : null,
            'count' => (int) $row->count,
        ];
    }

    /**
     * @param  Collection<int, \stdClass>  $sessions
     * @return array<string, mixed>
     */
    private function nightOwlSessions(Collection $sessions): array
    {
        $night = $sessions->filter(fn (\stdClass $s) => (int) Carbon::parse((string) $s->started_at)->format('H') < 4);
        $pct = $sessions->count() > 0 ? (int) round(($night->count() / $sessions->count()) * 100) : 0;

        return [
            'count' => $night->count(),
            'percent' => $pct,
        ];
    }

    /** @return array<string, mixed>|null */
    private function boundarySession(int $year, int $month, string $which): ?array
    {
        $id = $this->sessionsForMonth($year, $month)
            ->toBase()
            ->when(
                $which === 'first',
                fn ($q) => $q->orderBy('play_station_sessions.started_at'),
                fn ($q) => $q->orderByDesc('play_station_sessions.started_at'),
            )
            ->value('play_station_sessions.id');

        if ($id === null) {
            return null;
        }

        $session = PlayStationSession::with('game:id,name,display_name')->find((int) $id);

        if (! $session instanceof PlayStationSession) {
            return null;
        }

        return [
            'game' => $session->game->display_name ?? $session->game->name,
            'gameId' => $session->game->id,
            'date' => $session->started_at->format('d M Y'),
            'time' => $session->started_at->format('H:i'),
            'duration' => $this->formatMinutes($session->duration_minutes),
        ];
    }

    /**
     * @param  Collection<int, \stdClass>  $sessions
     * @return array<int, array<string, mixed>>
     */
    private function personalityArchetype(Collection $sessions, int $trophyTotal, float $totalHours): array
    {
        $archetypes = [];

        $afterTen = $sessions->filter(fn (\stdClass $s) => (int) Carbon::parse((string) $s->started_at)->format('H') >= 22);
        if ($sessions->count() > 0 && ($afterTen->count() / $sessions->count()) >= 0.6) {
            $archetypes[] = [
                'id' => 'night_owl',
                'label' => 'Night Owl',
                'icon' => '🌙',
                'desc' => (int) round(($afterTen->count() / $sessions->count()) * 100).'% of sessions after 22:00',
            ];
        }

        $avgMins = $sessions->count() > 0 ? $sessions->sum('duration_minutes') / $sessions->count() : 0;
        if ($avgMins >= 180) {
            $archetypes[] = [
                'id' => 'marathon',
                'label' => 'Marathon Gamer',
                'icon' => '🎮',
                'desc' => $this->formatMinutes((int) round($avgMins)).' avg session',
            ];
        }

        if ($totalHours > 0 && ($trophyTotal / $totalHours) >= 2) {
            $archetypes[] = [
                'id' => 'trophy_hunter',
                'label' => 'Trophy Hunter',
                'icon' => '🏆',
                'desc' => round($trophyTotal / $totalHours, 1).' trophies / hour',
            ];
        }

        $uniqueGames = $sessions->pluck('play_station_game_id')->unique();
        $totalMins = (int) $sessions->sum('duration_minutes');
        if ($uniqueGames->count() >= 4 && $totalMins > 0) {
            $maxGameMins = (int) $sessions
                ->groupBy('play_station_game_id')
                ->map(fn ($g) => (int) $g->sum('duration_minutes'))
                ->max();
            if ($maxGameMins / $totalMins < 0.6) {
                $archetypes[] = [
                    'id' => 'variety',
                    'label' => 'Variety Seeker',
                    'icon' => '🎲',
                    'desc' => $uniqueGames->count().' different games',
                ];
            }
        }

        if (empty($archetypes)) {
            $archetypes[] = [
                'id' => 'casual',
                'label' => 'Casual Gamer',
                'icon' => '🕹️',
                'desc' => 'Playing at your own pace',
            ];
        }

        return $archetypes;
    }

    /** @return Collection<int, array{id: int, label: string, image_url: string|null, platform: string, hours: float, session_count: int}> */
    private function newGamesInMonth(int $year, int $month): Collection
    {
        $start = Carbon::parse(sprintf('%d-%02d-01', $year, $month))->startOfDay();
        $end = Carbon::parse(sprintf('%d-%02d-01', $year, $month))->endOfMonth()->endOfDay();

        $newIds = PlayStationSession::query()
            ->toBase()
            ->selectRaw('play_station_game_id')
            ->groupBy('play_station_game_id')
            ->havingRaw('MIN(started_at) BETWEEN ? AND ?', [$start, $end])
            ->pluck('play_station_game_id');

        if ($newIds->isEmpty()) {
            return collect();
        }

        return $this->sessionsForMonth($year, $month)
            ->toBase()
            ->whereIn('play_station_sessions.play_station_game_id', $newIds)
            ->selectRaw('
                play_station_games.id,
                play_station_games.name,
                play_station_games.display_name,
                play_station_games.image_url,
                play_station_games.platform,
                SUM(play_station_sessions.duration_minutes) as total_minutes,
                COUNT(*) as session_count
            ')
            ->groupBy(
                'play_station_games.id',
                'play_station_games.name',
                'play_station_games.display_name',
                'play_station_games.image_url',
                'play_station_games.platform',
            )
            ->orderByDesc('total_minutes')
            ->get()
            ->map(fn (\stdClass $row) => [
                'id' => (int) $row->id,
                'label' => (string) ($row->display_name ?? $row->name),
                'image_url' => $row->image_url !== null ? (string) $row->image_url : null,
                'platform' => (string) $row->platform,
                'hours' => round((float) $row->total_minutes / 60, 1),
                'session_count' => (int) $row->session_count,
            ]);
    }

    /**
     * @param  Collection<int, \stdClass>  $sessions
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function calendarHeatmap(Collection $sessions, int $year, int $month): array
    {
        $byDay = $sessions
            ->groupBy(fn (\stdClass $s) => Carbon::parse((string) $s->started_at)->format('Y-m-d'))
            ->map(fn ($g) => (int) $g->sum('duration_minutes'));

        $maxMinutes = (int) ($byDay->max() ?: 1);
        $startOfMonth = Carbon::parse(sprintf('%d-%02d-01', $year, $month))->startOfWeek(Carbon::MONDAY);
        $endOfMonth = Carbon::parse(sprintf('%d-%02d-01', $year, $month))->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $weeks = [];
        $current = $startOfMonth->copy();

        while ($current->lte($endOfMonth)) {
            $week = [];
            for ($d = 0; $d < 7; $d++) {
                $dateKey = $current->format('Y-m-d');
                $mins = $byDay->get($dateKey, 0);
                $inMonth = (int) $current->format('n') === $month && (int) $current->format('Y') === $year;
                $week[] = [
                    'date' => $dateKey,
                    'day' => $inMonth ? (int) $current->format('j') : null,
                    'minutes' => $mins,
                    'level' => $inMonth ? $this->heatmapLevel((int) $mins, $maxMinutes) : -1,
                    'in_month' => $inMonth,
                    'formatted' => $mins > 0 ? $this->formatMinutes((int) $mins) : null,
                ];
                $current->addDay();
            }
            $weeks[] = $week;
        }

        return $weeks;
    }

    private function heatmapLevel(int $minutes, int $max): int
    {
        if ($minutes === 0) {
            return 0;
        }
        $ratio = $minutes / $max;

        return match (true) {
            $ratio <= 0.25 => 1,
            $ratio <= 0.5 => 2,
            $ratio <= 0.75 => 3,
            default => 4,
        };
    }

    /** @return Builder<PlayStationSession> */
    private function sessionsForMonth(int $year, int $month): Builder
    {
        return PlayStationSession::query()
            ->join('play_station_games', 'play_station_sessions.play_station_game_id', '=', 'play_station_games.id')
            ->where(function (Builder $q): void {
                $q->whereNull('play_station_games.released_at')
                    ->orWhereColumn('play_station_sessions.started_at', '>=', 'play_station_games.released_at');
            })
            ->whereYear('play_station_sessions.started_at', $year)
            ->whereMonth('play_station_sessions.started_at', $month);
    }

    private function formatMinutes(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes.'m';
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return $m > 0 ? "{$h}h {$m}m" : "{$h}h";
    }
}

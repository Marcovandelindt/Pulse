<?php

declare(strict_types=1);

namespace App\Queries\Gaming;

use App\Models\PlayStationGame;
use App\Models\PlayStationSession;
use App\Models\PlayStationTrophy;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class PlayStationWrappedQuery
{
    /** @return array<string, mixed> */
    public function handle(int $year): array
    {
        $sessions = $this->sessionsForYear($year)->get([
            'play_station_sessions.id',
            'play_station_sessions.play_station_game_id',
            'play_station_sessions.duration_minutes',
            'play_station_sessions.started_at',
        ]);

        if ($sessions->isEmpty()) {
            return ['hasData' => false, 'year' => $year];
        }

        $totalMinutes  = (int) $sessions->sum('duration_minutes');
        $totalHours    = round($totalMinutes / 60, 1);
        $totalSessions = $sessions->count();
        $uniqueGames   = $sessions->pluck('play_station_game_id')->unique()->count();

        $uniqueDays    = $sessions
            ->map(fn ($s) => Carbon::parse($s->started_at)->format('Y-m-d'))
            ->unique()
            ->count();

        $avgSessionMinutes = $totalSessions > 0 ? (int) round($totalMinutes / $totalSessions) : 0;
        $avgHoursPerDay    = $uniqueDays > 0 ? round($totalHours / $uniqueDays, 1) : 0;

        $lastYearMinutes = (int) $this->sessionsForYear($year - 1)
            ->sum('play_station_sessions.duration_minutes');

        $vsLastYear = $lastYearMinutes > 0
            ? round((($totalMinutes - $lastYearMinutes) / $lastYearMinutes) * 100, 1)
            : null;

        $trophies = $this->trophiesForYear($year);

        return [
            'hasData'           => true,
            'year'              => $year,
            'totalHours'        => $totalHours,
            'totalMinutes'      => $totalMinutes,
            'totalSessions'     => $totalSessions,
            'uniqueGames'       => $uniqueGames,
            'newGames'          => $this->newGamesCount($year),
            'newGamesDetail'    => $this->newGamesDetail($year, $uniqueGames),
            'totalDaysPlayed'   => $uniqueDays,
            'avgSessionMinutes' => $avgSessionMinutes,
            'avgSessionFormatted' => $this->formatMinutes($avgSessionMinutes),
            'avgHoursPerDay'    => $avgHoursPerDay,
            'vsLastYear'        => $vsLastYear,
            'topGames'          => $this->topGames($year),
            'timeOfDay'         => $this->timeOfDay($sessions),
            'weekdayBreakdown'  => $this->weekdayBreakdown($sessions),
            'weekdayVsWeekend'  => $this->weekdayVsWeekend($sessions),
            'monthlyChart'      => $this->monthlyChart($year),
            'bestMonth'         => $this->bestMonth($sessions),
            'busiestDay'        => $this->busiestDay($sessions),
            'busiestWeek'       => $this->busiestWeek($year),
            'longestSession'    => $this->longestSession($year),
            'longestStreak'     => $this->longestStreak($sessions),
            'platformBreakdown' => $this->platformBreakdown($year),
            'trophies'          => $trophies,
            'bestTrophyDay'     => $this->bestTrophyDay($year),
            'topTrophyGame'     => $this->topTrophyGame($year),
            'nightOwl'          => $this->nightOwlSessions($sessions),
            'firstSession'      => $this->firstSession($year),
            'lastSession'       => $this->lastSession($year),
            'personalityArchetype' => $this->personalityArchetype($sessions, $trophies['total'], $totalHours),
            'completedThisYear'    => $this->completedThisYear($year),
            'mostImproved'         => $this->mostImproved($year),
            'trophyHaulByMonth'    => $this->trophyHaulByMonth($year),
            'oneThatGotAway'       => $this->oneThatGotAway($year),
            'calendarHeatmap'      => $this->calendarHeatmap($sessions, $year),
        ];
    }

    /** @return array<int, int> */
    public function availableYears(): array
    {
        return PlayStationSession::query()
            ->selectRaw('YEAR(started_at) as year')
            ->groupByRaw('YEAR(started_at)')
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($y) => (int) $y)
            ->all();
    }

    /** @return Collection<int, array<string, mixed>> */
    private function topGames(int $year): Collection
    {
        return $this->sessionsForYear($year)
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
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'id'            => $row->id,
                'label'         => $row->display_name ?? $row->name,
                'image_url'     => $row->image_url,
                'platform'      => $row->platform,
                'hours'         => round((float) $row->total_minutes / 60, 1),
                'minutes'       => (int) $row->total_minutes,
                'session_count' => (int) $row->session_count,
            ]);
    }

    /** @return array<string, mixed> */
    private function timeOfDay(Collection $sessions): array
    {
        $buckets = ['morning' => 0, 'afternoon' => 0, 'evening' => 0, 'night' => 0];

        foreach ($sessions as $s) {
            $hour = (int) Carbon::parse($s->started_at)->format('H');
            $key  = match (true) {
                $hour >= 6  && $hour < 12 => 'morning',
                $hour >= 12 && $hour < 18 => 'afternoon',
                $hour >= 18 && $hour < 24 => 'evening',
                default                   => 'night',
            };
            $buckets[$key] += $s->duration_minutes;
        }

        $total = array_sum($buckets) ?: 1;

        $labels = [
            'morning'   => ['label' => 'Morning',   'range' => '6 – 12',  'icon' => '🌅'],
            'afternoon' => ['label' => 'Afternoon',  'range' => '12 – 18', 'icon' => '☀️'],
            'evening'   => ['label' => 'Evening',    'range' => '18 – 24', 'icon' => '🌆'],
            'night'     => ['label' => 'Late night', 'range' => '0 – 6',   'icon' => '🌙'],
        ];

        $result = [];
        foreach ($buckets as $key => $minutes) {
            $result[$key] = [
                ...$labels[$key],
                'hours'   => round($minutes / 60, 1),
                'percent' => round(($minutes / $total) * 100),
            ];
        }

        return $result;
    }

    /** @return Collection<int, array<string, mixed>> */
    private function weekdayBreakdown(Collection $sessions): Collection
    {
        $dayNames = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

        $byDay = $sessions->groupBy(fn ($s) => (int) Carbon::parse($s->started_at)->isoFormat('E'));

        $maxMinutes = 1;
        foreach ($dayNames as $iso => $_) {
            $mins = (int) ($byDay->get($iso)?->sum('duration_minutes') ?? 0);
            if ($mins > $maxMinutes) {
                $maxMinutes = $mins;
            }
        }

        return collect($dayNames)->map(function (string $label, int $iso) use ($byDay, $maxMinutes) {
            $group   = $byDay->get($iso) ?? collect();
            $minutes = (int) $group->sum('duration_minutes');

            return [
                'label'   => $label,
                'hours'   => round($minutes / 60, 1),
                'count'   => $group->count(),
                'percent' => round(($minutes / $maxMinutes) * 100),
            ];
        })->values();
    }

    /** @return array<string, mixed> */
    private function weekdayVsWeekend(Collection $sessions): array
    {
        $weekday = $sessions->filter(fn ($s) => ! in_array(Carbon::parse($s->started_at)->dayOfWeek, [0, 6], true));
        $weekend = $sessions->filter(fn ($s) => in_array(Carbon::parse($s->started_at)->dayOfWeek, [0, 6], true));

        $wdMins = (int) $weekday->sum('duration_minutes');
        $weMins = (int) $weekend->sum('duration_minutes');
        $total  = ($wdMins + $weMins) ?: 1;

        return [
            'weekday' => [
                'hours'    => round($wdMins / 60, 1),
                'sessions' => $weekday->count(),
                'percent'  => round(($wdMins / $total) * 100),
            ],
            'weekend' => [
                'hours'    => round($weMins / 60, 1),
                'sessions' => $weekend->count(),
                'percent'  => round(($weMins / $total) * 100),
            ],
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function monthlyChart(int $year): Collection
    {
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        $raw = $this->sessionsForYear($year)
            ->selectRaw('MONTH(play_station_sessions.started_at) as m, SUM(play_station_sessions.duration_minutes) as total_minutes, COUNT(*) as session_count')
            ->groupByRaw('MONTH(play_station_sessions.started_at)')
            ->get()
            ->keyBy('m');

        $maxMinutes = (int) ($raw->max('total_minutes') ?: 1);

        return collect(range(1, 12))->map(fn (int $m) => [
            'label'    => $monthNames[$m - 1],
            'hours'    => round((float) ($raw->get($m)?->total_minutes ?? 0) / 60, 1),
            'sessions' => (int) ($raw->get($m)?->session_count ?? 0),
            'percent'  => round(((float) ($raw->get($m)?->total_minutes ?? 0) / $maxMinutes) * 100),
        ]);
    }

    /** @return array<string, mixed> */
    private function bestMonth(Collection $sessions): array
    {
        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        $byMonth  = $sessions->groupBy(fn ($s) => (int) Carbon::parse($s->started_at)->month);
        $bestNum  = $byMonth->map(fn ($g) => (int) $g->sum('duration_minutes'))->sortDesc()->keys()->first();
        $bestMins = $byMonth->get($bestNum)?->sum('duration_minutes') ?? 0;

        return [
            'name'     => $bestNum ? $monthNames[$bestNum] : null,
            'hours'    => $bestNum ? round($bestMins / 60, 1) : 0,
            'sessions' => $bestNum ? $byMonth->get($bestNum)->count() : 0,
        ];
    }

    /** @return array<string, mixed>|null */
    private function busiestDay(Collection $sessions): ?array
    {
        $byDay = $sessions->groupBy(fn ($s) => Carbon::parse($s->started_at)->format('Y-m-d'));
        if ($byDay->isEmpty()) {
            return null;
        }

        $best    = $byDay->map(fn ($g) => (int) $g->sum('duration_minutes'))->sortDesc()->keys()->first();
        $group   = $byDay->get($best);
        $minutes = (int) $group->sum('duration_minutes');

        $daySessions = PlayStationSession::query()
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
            ->map(fn ($s) => [
                'game'     => $s->display_name ?? $s->name,
                'gameId'   => $s->game_id,
                'time'     => Carbon::parse($s->started_at)->format('H:i'),
                'duration' => $this->formatMinutes($s->duration_minutes),
            ]);

        return [
            'date'      => Carbon::parse($best)->format('l, d M Y'),
            'hours'     => round($minutes / 60, 1),
            'sessions'  => $group->count(),
            'formatted' => $this->formatMinutes($minutes),
            'breakdown' => $daySessions,
        ];
    }

    /** @return array<string, mixed>|null */
    private function busiestWeek(int $year): ?array
    {
        $row = $this->sessionsForYear($year)
            ->selectRaw('YEARWEEK(play_station_sessions.started_at, 1) as yw, SUM(play_station_sessions.duration_minutes) as total_minutes, COUNT(*) as session_count')
            ->groupByRaw('YEARWEEK(play_station_sessions.started_at, 1)')
            ->orderByDesc('total_minutes')
            ->first();

        if (! $row) {
            return null;
        }

        $yw     = (string) $row->yw;
        $monday = Carbon::now()->setISODate((int) substr($yw, 0, 4), (int) substr($yw, 4))->startOfWeek();
        $sunday = $monday->copy()->endOfWeek();

        $topGame = PlayStationSession::query()
            ->join('play_station_games', 'play_station_sessions.play_station_game_id', '=', 'play_station_games.id')
            ->whereBetween('play_station_sessions.started_at', [$monday, $sunday])
            ->selectRaw('play_station_games.id, play_station_games.name, play_station_games.display_name, SUM(play_station_sessions.duration_minutes) as total_minutes')
            ->groupBy('play_station_games.id', 'play_station_games.name', 'play_station_games.display_name')
            ->orderByDesc('total_minutes')
            ->first();

        return [
            'label'       => $monday->format('d M') . ' – ' . $sunday->format('d M Y'),
            'hours'       => round((float) $row->total_minutes / 60, 1),
            'sessions'    => (int) $row->session_count,
            'topGame'     => $topGame ? ($topGame->display_name ?? $topGame->name) : null,
            'topGameId'   => $topGame?->id,
            'topGameHours' => $topGame ? round((float) $topGame->total_minutes / 60, 1) : null,
        ];
    }

    /** @return array<string, mixed>|null */
    private function longestSession(int $year): ?array
    {
        $session = $this->sessionsForYear($year)
            ->with('game:id,name,display_name')
            ->orderByDesc('play_station_sessions.duration_minutes')
            ->first([
                'play_station_sessions.play_station_game_id',
                'play_station_sessions.duration_minutes',
                'play_station_sessions.started_at',
            ]);

        if (! $session) {
            return null;
        }

        return [
            'game'      => $session->game?->label,
            'gameId'    => $session->game?->id,
            'date'      => $session->started_at->format('d M Y'),
            'time'      => $session->started_at->format('H:i'),
            'duration'  => $this->formatMinutes($session->duration_minutes),
            'minutes'   => $session->duration_minutes,
        ];
    }

    /** @return array<string, mixed> */
    private function longestStreak(Collection $sessions): array
    {
        $dates = $sessions
            ->map(fn ($s) => Carbon::parse($s->started_at)->format('Y-m-d'))
            ->unique()
            ->sort()
            ->values();

        if ($dates->isEmpty()) {
            return ['days' => 0, 'startDate' => null, 'endDate' => null];
        }

        $best  = 1;
        $run   = 1;
        $runStart = $dates[0];
        $bestStart = $dates[0];
        $bestEnd   = $dates[0];

        for ($i = 1; $i < $dates->count(); $i++) {
            $diff = Carbon::parse($dates[$i - 1])->diffInDays(Carbon::parse($dates[$i]));

            if ($diff === 1) {
                $run++;
                if ($run > $best) {
                    $best      = $run;
                    $bestStart = $runStart;
                    $bestEnd   = $dates[$i];
                }
            } else {
                $run      = 1;
                $runStart = $dates[$i];
            }
        }

        return [
            'days'      => $best,
            'startDate' => Carbon::parse($bestStart)->format('d M'),
            'endDate'   => Carbon::parse($bestEnd)->format('d M Y'),
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function platformBreakdown(int $year): Collection
    {
        $rows = $this->sessionsForYear($year)
            ->selectRaw('play_station_games.platform, SUM(play_station_sessions.duration_minutes) as total_minutes, COUNT(*) as session_count, COUNT(DISTINCT play_station_games.id) as game_count')
            ->groupBy('play_station_games.platform')
            ->orderByDesc('total_minutes')
            ->get();

        $maxMinutes = (int) ($rows->max('total_minutes') ?: 1);

        return $rows->map(fn ($row) => [
            'platform'     => $row->platform,
            'hours'        => round((float) $row->total_minutes / 60, 1),
            'sessions'     => (int) $row->session_count,
            'game_count'   => (int) $row->game_count,
            'percent'      => round(((float) $row->total_minutes / $maxMinutes) * 100),
        ]);
    }

    /** @return array<string, int> */
    private function trophiesForYear(int $year): array
    {
        $byType = PlayStationTrophy::query()
            ->where('is_earned', true)
            ->whereYear('earned_at', $year)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->keyBy('type')
            ->map(fn ($r) => (int) $r->count);

        return [
            'total'    => (int) $byType->sum(),
            'platinum' => (int) ($byType->get('platinum') ?? 0),
            'gold'     => (int) ($byType->get('gold') ?? 0),
            'silver'   => (int) ($byType->get('silver') ?? 0),
            'bronze'   => (int) ($byType->get('bronze') ?? 0),
        ];
    }

    /** @return array<string, mixed>|null */
    private function bestTrophyDay(int $year): ?array
    {
        $row = PlayStationTrophy::query()
            ->where('is_earned', true)
            ->whereYear('earned_at', $year)
            ->whereNotNull('earned_at')
            ->selectRaw('DATE(earned_at) as day, COUNT(*) as count')
            ->groupByRaw('DATE(earned_at)')
            ->orderByDesc('count')
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'date'  => Carbon::parse($row->day)->format('d M Y'),
            'day'   => Carbon::parse($row->day)->format('l'),
            'count' => (int) $row->count,
        ];
    }

    /** @return array<string, mixed>|null */
    private function topTrophyGame(int $year): ?array
    {
        $row = PlayStationTrophy::query()
            ->where('is_earned', true)
            ->whereYear('earned_at', $year)
            ->whereNotNull('earned_at')
            ->join('play_station_games', 'play_station_trophies.play_station_game_id', '=', 'play_station_games.id')
            ->selectRaw('play_station_games.id, play_station_games.name, play_station_games.display_name, play_station_games.image_url, COUNT(*) as count')
            ->groupBy('play_station_games.id', 'play_station_games.name', 'play_station_games.display_name', 'play_station_games.image_url')
            ->orderByDesc('count')
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'label'     => $row->display_name ?? $row->name,
            'gameId'    => $row->id,
            'image_url' => $row->image_url,
            'count'     => (int) $row->count,
        ];
    }

    /** @return array<string, mixed> */
    private function nightOwlSessions(Collection $sessions): array
    {
        $night = $sessions->filter(fn ($s) => (int) Carbon::parse($s->started_at)->format('H') < 4);
        $pct   = $sessions->count() > 0 ? round(($night->count() / $sessions->count()) * 100) : 0;

        return [
            'count'   => $night->count(),
            'percent' => $pct,
        ];
    }

    /** @return array<string, mixed>|null */
    private function firstSession(int $year): ?array
    {
        $session = $this->sessionsForYear($year)
            ->with('game:id,name,display_name')
            ->orderBy('play_station_sessions.started_at')
            ->first([
                'play_station_sessions.play_station_game_id',
                'play_station_sessions.started_at',
                'play_station_sessions.duration_minutes',
            ]);

        if (! $session) {
            return null;
        }

        return [
            'game'     => $session->game?->label,
            'gameId'   => $session->game?->id,
            'date'     => $session->started_at->format('d M Y'),
            'time'     => $session->started_at->format('H:i'),
            'duration' => $this->formatMinutes($session->duration_minutes),
        ];
    }

    /** @return array<string, mixed>|null */
    private function lastSession(int $year): ?array
    {
        $session = $this->sessionsForYear($year)
            ->with('game:id,name,display_name')
            ->orderByDesc('play_station_sessions.started_at')
            ->first([
                'play_station_sessions.play_station_game_id',
                'play_station_sessions.started_at',
                'play_station_sessions.duration_minutes',
            ]);

        if (! $session) {
            return null;
        }

        return [
            'game'     => $session->game?->label,
            'gameId'   => $session->game?->id,
            'date'     => $session->started_at->format('d M Y'),
            'time'     => $session->started_at->format('H:i'),
            'duration' => $this->formatMinutes($session->duration_minutes),
        ];
    }

    private function newGamesCount(int $year): int
    {
        return (int) $this->newGameIds($year)->count();
    }

    /** @return array<string, mixed> */
    private function newGamesDetail(int $year, int $uniqueGames): array
    {
        $newIds = $this->newGameIds($year)->pluck('play_station_game_id');

        if ($newIds->isEmpty()) {
            return ['count' => 0, 'percent' => 0, 'totalHours' => 0, 'top' => collect()];
        }

        // Total minutes spent on new games this year
        $totalNewMinutes = (int) $this->sessionsForYear($year)
            ->whereIn('play_station_sessions.play_station_game_id', $newIds)
            ->sum('play_station_sessions.duration_minutes');

        // Top 5 new games by hours this year
        $top = $this->sessionsForYear($year)
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
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'id'            => $row->id,
                'label'         => $row->display_name ?? $row->name,
                'image_url'     => $row->image_url,
                'platform'      => $row->platform,
                'hours'         => round((float) $row->total_minutes / 60, 1),
                'session_count' => (int) $row->session_count,
            ]);

        return [
            'count'      => $newIds->count(),
            'percent'    => $uniqueGames > 0 ? round(($newIds->count() / $uniqueGames) * 100) : 0,
            'totalHours' => round($totalNewMinutes / 60, 1),
            'top'        => $top,
        ];
    }

    /** Returns a query of game IDs first played in the given year (across all sessions, not just valid ones). */
    private function newGameIds(int $year): \Illuminate\Support\Collection
    {
        return PlayStationSession::query()
            ->selectRaw('play_station_game_id, MIN(started_at) as first_ever')
            ->groupBy('play_station_game_id')
            ->havingRaw('YEAR(MIN(started_at)) = ?', [$year])
            ->get();
    }

    private function formatMinutes(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes . 'm';
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return $m > 0 ? "{$h}h {$m}m" : "{$h}h";
    }

    /** @return array<int, array<string, mixed>> */
    private function personalityArchetype(Collection $sessions, int $trophyTotal, float $totalHours): array
    {
        $archetypes = [];

        $afterTen = $sessions->filter(fn ($s) => (int) Carbon::parse($s->started_at)->format('H') >= 22);
        if ($sessions->count() > 0 && ($afterTen->count() / $sessions->count()) >= 0.6) {
            $archetypes[] = [
                'id'   => 'night_owl',
                'label' => 'Night Owl',
                'icon'  => '🌙',
                'desc'  => round(($afterTen->count() / $sessions->count()) * 100) . '% of sessions after 22:00',
            ];
        }

        $avgMins = $sessions->count() > 0 ? $sessions->sum('duration_minutes') / $sessions->count() : 0;
        if ($avgMins >= 180) {
            $archetypes[] = [
                'id'    => 'marathon',
                'label' => 'Marathon Gamer',
                'icon'  => '🎮',
                'desc'  => $this->formatMinutes((int) round($avgMins)) . ' avg session',
            ];
        }

        if ($totalHours > 0 && ($trophyTotal / $totalHours) >= 2) {
            $archetypes[] = [
                'id'    => 'trophy_hunter',
                'label' => 'Trophy Hunter',
                'icon'  => '🏆',
                'desc'  => round($trophyTotal / $totalHours, 1) . ' trophies / hour',
            ];
        }

        $uniqueGames = $sessions->pluck('play_station_game_id')->unique();
        $totalMins   = (int) $sessions->sum('duration_minutes');
        if ($uniqueGames->count() >= 5 && $totalMins > 0) {
            $maxGameMins = (int) $sessions
                ->groupBy('play_station_game_id')
                ->map(fn ($g) => (int) $g->sum('duration_minutes'))
                ->max();
            if ($maxGameMins / $totalMins < 0.6) {
                $archetypes[] = [
                    'id'    => 'variety',
                    'label' => 'Variety Seeker',
                    'icon'  => '🎲',
                    'desc'  => $uniqueGames->count() . ' different games',
                ];
            }
        }

        if (empty($archetypes)) {
            $archetypes[] = [
                'id'    => 'casual',
                'label' => 'Casual Gamer',
                'icon'  => '🕹️',
                'desc'  => 'Playing at your own pace',
            ];
        }

        return $archetypes;
    }

    /** @return Collection<int, array<string, mixed>> */
    private function completedThisYear(int $year): Collection
    {
        $games = PlayStationGame::query()
            ->whereYear('completed_at', $year)
            ->orderBy('completed_at')
            ->get(['id', 'name', 'display_name', 'image_url', 'completed_at']);

        if ($games->isEmpty()) {
            return collect();
        }

        $gameIds = $games->pluck('id');

        $yearStats = $this->sessionsForYear($year)
            ->whereIn('play_station_sessions.play_station_game_id', $gameIds)
            ->selectRaw('play_station_games.id, SUM(play_station_sessions.duration_minutes) as year_minutes, MIN(play_station_sessions.started_at) as first_session')
            ->groupBy('play_station_games.id')
            ->get()
            ->keyBy('id');

        $allTime = PlayStationSession::query()
            ->whereIn('play_station_game_id', $gameIds)
            ->selectRaw('play_station_game_id, SUM(duration_minutes) as total_minutes')
            ->groupBy('play_station_game_id')
            ->get()
            ->keyBy('play_station_game_id');

        return $games->map(function ($game) use ($yearStats, $allTime) {
            $s            = $yearStats->get($game->id);
            $a            = $allTime->get($game->id);
            $completedAt  = Carbon::parse($game->completed_at);
            $firstSession = $s ? Carbon::parse($s->first_session) : null;

            return [
                'id'               => $game->id,
                'label'            => $game->display_name ?? $game->name,
                'image_url'        => $game->image_url,
                'completed_at'     => $completedAt->format('d M Y'),
                'year_hours'       => $s ? round((float) $s->year_minutes / 60, 1) : 0,
                'total_hours'      => $a ? round((float) $a->total_minutes / 60, 1) : 0,
                'first_session'    => $firstSession?->format('d M Y'),
                'days_to_complete' => $firstSession ? (int) $firstSession->diffInDays($completedAt) + 1 : null,
            ];
        });
    }

    /** @return array<string, mixed>|null */
    private function mostImproved(int $year): ?array
    {
        $row = PlayStationTrophy::query()
            ->where('is_earned', true)
            ->whereYear('earned_at', $year)
            ->whereNotNull('earned_at')
            ->join('play_station_games', 'play_station_trophies.play_station_game_id', '=', 'play_station_games.id')
            ->where('play_station_games.completion_percentage', '<', 100)
            ->selectRaw('play_station_games.id, play_station_games.name, play_station_games.display_name, play_station_games.image_url, play_station_games.completion_percentage, COUNT(*) as trophies_this_year')
            ->groupBy('play_station_games.id', 'play_station_games.name', 'play_station_games.display_name', 'play_station_games.image_url', 'play_station_games.completion_percentage')
            ->orderByDesc('trophies_this_year')
            ->first();

        if (! $row) {
            return null;
        }

        $gameId    = $row->id;
        $total     = PlayStationTrophy::where('play_station_game_id', $gameId)->count();
        $before    = PlayStationTrophy::where('play_station_game_id', $gameId)
            ->where('is_earned', true)
            ->where(fn ($q) => $q->whereNull('earned_at')->orWhereYear('earned_at', '<', $year))
            ->count();

        $pctBefore = $total > 0 ? (int) round(($before / $total) * 100) : 0;

        return [
            'id'                 => $row->id,
            'label'              => $row->display_name ?? $row->name,
            'image_url'          => $row->image_url,
            'trophies_this_year' => (int) $row->trophies_this_year,
            'pct_before'         => $pctBefore,
            'pct_after'          => (int) round((float) $row->completion_percentage),
            'gain'               => max(0, (int) round((float) $row->completion_percentage) - $pctBefore),
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function trophyHaulByMonth(int $year): Collection
    {
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        $raw = PlayStationTrophy::query()
            ->where('is_earned', true)
            ->whereYear('earned_at', $year)
            ->whereNotNull('earned_at')
            ->selectRaw('MONTH(earned_at) as m, COUNT(*) as count')
            ->groupByRaw('MONTH(earned_at)')
            ->get()
            ->keyBy('m');

        $max = (int) ($raw->max('count') ?: 1);

        return collect(range(1, 12))->map(fn (int $m) => [
            'label'   => $monthNames[$m - 1],
            'count'   => (int) ($raw->get($m)?->count ?? 0),
            'percent' => (int) round(((int) ($raw->get($m)?->count ?? 0) / $max) * 100),
        ]);
    }

    /** @return array<string, mixed>|null */
    private function oneThatGotAway(int $year): ?array
    {
        $row = $this->sessionsForYear($year)
            ->where('play_station_games.completion_percentage', '<', 100)
            ->selectRaw('play_station_games.id, play_station_games.name, play_station_games.display_name, play_station_games.image_url, play_station_games.completion_percentage, SUM(play_station_sessions.duration_minutes) as year_minutes, COUNT(*) as session_count')
            ->groupBy('play_station_games.id', 'play_station_games.name', 'play_station_games.display_name', 'play_station_games.image_url', 'play_station_games.completion_percentage')
            ->orderByDesc('year_minutes')
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'id'            => $row->id,
            'label'         => $row->display_name ?? $row->name,
            'image_url'     => $row->image_url,
            'year_hours'    => round((float) $row->year_minutes / 60, 1),
            'session_count' => (int) $row->session_count,
            'completion'    => (int) round((float) $row->completion_percentage),
        ];
    }

    /**
     * @param  Collection<int, object>  $sessions
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function calendarHeatmap(Collection $sessions, int $year): array
    {
        $byDay = $sessions
            ->groupBy(fn ($s) => Carbon::parse($s->started_at)->format('Y-m-d'))
            ->map(fn ($g) => (int) $g->sum('duration_minutes'));

        $maxMinutes  = (int) ($byDay->max() ?: 1);
        $startOfYear = Carbon::create($year, 1, 1)->startOfWeek(Carbon::MONDAY);
        $endOfYear   = Carbon::create($year, 12, 31)->endOfWeek(Carbon::SUNDAY);

        $weeks   = [];
        $current = $startOfYear->copy();

        while ($current <= $endOfYear) {
            $week = [];
            for ($d = 0; $d < 7; $d++) {
                $dateKey = $current->format('Y-m-d');
                $mins    = $byDay->get($dateKey, 0);
                $inYear  = (int) $current->format('Y') === $year;
                $week[]  = [
                    'date'    => $dateKey,
                    'minutes' => $mins,
                    'level'   => $inYear ? $this->heatmapLevel($mins, $maxMinutes) : -1,
                    'in_year' => $inYear,
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
            $ratio <= 0.5  => 2,
            $ratio <= 0.75 => 3,
            default        => 4,
        };
    }

    private function sessionsForYear(int $year): Builder
    {
        return PlayStationSession::query()
            ->join('play_station_games', 'play_station_sessions.play_station_game_id', '=', 'play_station_games.id')
            ->where(function (Builder $q): void {
                $q->whereNull('play_station_games.released_at')
                  ->orWhereColumn('play_station_sessions.started_at', '>=', 'play_station_games.released_at');
            })
            ->whereYear('play_station_sessions.started_at', $year);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Enums\BacklogStatus;
use App\Http\Controllers\Controller;
use App\Models\PlayStationGame;
use App\Models\PlayStationSession;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;

final class PlayStationStatsController extends Controller
{
    public function index(): View
    {
        $totalMinutes      = (int) $this->validSessionsBase()->sum('play_station_sessions.duration_minutes');
        $totalHours        = round($totalMinutes / 60, 1);
        $totalSessions     = $this->validSessionsBase()->count();
        $totalGames        = PlayStationGame::count();
        $avgSessionMinutes = $totalSessions > 0 ? (int) round($totalMinutes / $totalSessions) : 0;

        $platformHours   = $this->platformBreakdown();
        $personalRecords = $this->personalRecords();
        $weekdayPatterns = $this->weekdayPatterns();
        $hourlyPatterns  = $this->hourlyPatterns();
        $monthlyTrend    = $this->monthlyTrend();
        $libraryStats    = $this->libraryStats();
        $trophyStats     = $this->trophyStats();
        $consistency     = $this->consistencyStats();
        $yearInReview    = $this->yearInReview();

        return view('pages.playstation.stats', compact(
            'totalHours', 'totalSessions', 'totalGames', 'avgSessionMinutes',
            'platformHours',
            'personalRecords',
            'weekdayPatterns', 'hourlyPatterns', 'monthlyTrend',
            'libraryStats',
            'trophyStats',
            'consistency',
            'yearInReview',
        ));
    }

    /** @return Collection<int, array<string, mixed>> */
    private function platformBreakdown(): Collection
    {
        $rows = PlayStationGame::query()
            ->join('play_station_sessions', 'play_station_games.id', '=', 'play_station_sessions.play_station_game_id')
            ->whereRaw('(play_station_games.released_at IS NULL OR play_station_sessions.started_at >= play_station_games.released_at)')
            ->selectRaw('play_station_games.platform, SUM(play_station_sessions.duration_minutes) as total_minutes, COUNT(DISTINCT play_station_games.id) as game_count')
            ->groupBy('play_station_games.platform')
            ->orderByDesc('total_minutes')
            ->get();

        $maxMinutes = (int) ($rows->max('total_minutes') ?: 1);

        return $rows->map(fn ($row) => [
            'platform'   => $row->platform,
            'hours'      => round((float) $row->total_minutes / 60, 1),
            'game_count' => (int) $row->game_count,
            'percent'    => round(((float) $row->total_minutes / $maxMinutes) * 100),
            'color'      => match ($row->platform) {
                'PS5'    => '#003087',
                'PS4'    => '#00439c',
                'PS3'    => '#003791',
                'PSVITA' => '#0070d1',
                default  => '#444',
            },
        ]);
    }

    /** @return array<string, mixed> */
    private function personalRecords(): array
    {
        $longestSession = $this->validSessionsBase()
            ->with('game:id,name,display_name')
            ->orderByDesc('play_station_sessions.duration_minutes')
            ->first([
                'play_station_sessions.id',
                'play_station_sessions.play_station_game_id',
                'play_station_sessions.started_at',
                'play_station_sessions.duration_minutes',
            ]);

        $mostPlayedGame = PlayStationGame::query()
            ->join('play_station_sessions', 'play_station_games.id', '=', 'play_station_sessions.play_station_game_id')
            ->whereRaw('(play_station_games.released_at IS NULL OR play_station_sessions.started_at >= play_station_games.released_at)')
            ->selectRaw('play_station_games.id, play_station_games.name, play_station_games.display_name, play_station_games.image_url, SUM(play_station_sessions.duration_minutes) as total_minutes')
            ->groupBy('play_station_games.id', 'play_station_games.name', 'play_station_games.display_name', 'play_station_games.image_url')
            ->orderByDesc('total_minutes')
            ->first();

        $busiestDay = $this->validSessionsBase()
            ->selectRaw('DATE(play_station_sessions.started_at) as day, SUM(play_station_sessions.duration_minutes) as total_minutes, COUNT(*) as session_count')
            ->groupByRaw('DATE(play_station_sessions.started_at)')
            ->orderByDesc('total_minutes')
            ->first();

        $mostSessionsDay = $this->validSessionsBase()
            ->selectRaw('DATE(play_station_sessions.started_at) as day, COUNT(*) as session_count, SUM(play_station_sessions.duration_minutes) as total_minutes')
            ->groupByRaw('DATE(play_station_sessions.started_at)')
            ->orderByDesc('session_count')
            ->first();

        return [
            'longestSessionFormatted'  => $longestSession ? $this->formatMinutes($longestSession->duration_minutes) : null,
            'longestSessionGame'       => $longestSession?->game?->label,
            'longestSessionDate'       => $longestSession?->started_at?->format('d M Y'),
            'mostPlayedGameName'       => $mostPlayedGame?->label,
            'mostPlayedGameImage'      => $mostPlayedGame?->image_url,
            'mostPlayedGameId'         => $mostPlayedGame?->id,
            'mostPlayedFormatted'      => $mostPlayedGame ? $this->formatMinutes((int) $mostPlayedGame->total_minutes) : null,
            'busiestDayFormatted'      => $busiestDay ? $this->formatMinutes((int) $busiestDay->total_minutes) : null,
            'busiestDayDate'           => $busiestDay ? Carbon::parse($busiestDay->day)->format('d M Y') : null,
            'busiestDaySessions'       => $busiestDay ? (int) $busiestDay->session_count : null,
            'mostSessionsDayCount'     => $mostSessionsDay ? (int) $mostSessionsDay->session_count : null,
            'mostSessionsDayDate'      => $mostSessionsDay ? Carbon::parse($mostSessionsDay->day)->format('d M Y') : null,
            'mostSessionsDayFormatted' => $mostSessionsDay ? $this->formatMinutes((int) $mostSessionsDay->total_minutes) : null,
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function weekdayPatterns(): Collection
    {
        $days = collect([1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun']);

        $raw = $this->validSessionsBase()
            ->selectRaw('DAYOFWEEK(play_station_sessions.started_at) as dow, AVG(play_station_sessions.duration_minutes) as avg_minutes, COUNT(*) as count')
            ->groupByRaw('DAYOFWEEK(play_station_sessions.started_at)')
            ->get()
            ->keyBy('dow');

        // MySQL DAYOFWEEK: 1=Sunday … 7=Saturday — remap to 1=Monday … 7=Sunday
        return $days->map(function (string $label, int $iso) use ($raw) {
            $mysqlDow = $iso === 7 ? 1 : $iso + 1;
            $row = $raw->get($mysqlDow);

            return [
                'label'       => $label,
                'avg_minutes' => $row ? (int) round((float) $row->avg_minutes) : 0,
                'count'       => $row ? (int) $row->count : 0,
            ];
        })->values();
    }

    /** @return Collection<int, array<string, mixed>> */
    private function hourlyPatterns(): Collection
    {
        $raw = $this->validSessionsBase()
            ->selectRaw('HOUR(play_station_sessions.started_at) as hour, COUNT(*) as count')
            ->groupByRaw('HOUR(play_station_sessions.started_at)')
            ->get()
            ->keyBy('hour');

        return collect(range(0, 23))->map(fn (int $h) => [
            'hour'  => $h,
            'label' => sprintf('%02d', $h),
            'count' => (int) ($raw->get($h)?->count ?? 0),
        ]);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function monthlyTrend(): Collection
    {
        return $this->validSessionsBase()
            ->selectRaw("DATE_FORMAT(play_station_sessions.started_at, '%Y-%m') as month, SUM(play_station_sessions.duration_minutes) as total_minutes, COUNT(*) as session_count")
            ->groupByRaw("DATE_FORMAT(play_station_sessions.started_at, '%Y-%m')")
            ->orderByDesc('month')
            ->limit(12)
            ->get()
            ->map(fn ($row) => [
                'month'         => Carbon::createFromFormat('Y-m', $row->month)->format('M Y'),
                'hours'         => round((float) $row->total_minutes / 60, 1),
                'session_count' => (int) $row->session_count,
            ])
            ->sortBy('month')
            ->values();
    }

    /** @return array<string, mixed> */
    private function libraryStats(): array
    {
        $backlogCounts = PlayStationGame::query()
            ->whereNotNull('backlog_status')
            ->selectRaw('backlog_status, COUNT(*) as count')
            ->groupBy('backlog_status')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->backlog_status->value => (int) $r->count]);

        $statuses = collect(BacklogStatus::cases())->map(fn (BacklogStatus $s) => [
            'value' => $s->value,
            'label' => $s->label(),
            'icon'  => $s->icon(),
            'color' => match ($s->color()) {
                'gray'   => '#6b7280',
                'blue'   => '#3b82f6',
                'green'  => '#22c55e',
                'yellow' => '#eab308',
                'red'    => '#ef4444',
                default  => '#6b7280',
            },
            'count' => (int) ($backlogCounts->get($s->value) ?? 0),
        ]);

        $total = $statuses->sum('count');

        $platformCounts = PlayStationGame::query()
            ->selectRaw('platform, COUNT(*) as count')
            ->groupBy('platform')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($r) => ['platform' => $r->platform, 'count' => (int) $r->count]);

        $avgCompletion = round((float) PlayStationGame::query()->whereNotNull('completion_percentage')->avg('completion_percentage'), 1);

        return [
            'statuses'       => $statuses,
            'statusTotal'    => $total,
            'platformCounts' => $platformCounts,
            'avgCompletion'  => $avgCompletion,
        ];
    }

    /** @return array<string, mixed> */
    private function trophyStats(): array
    {
        $byType = \App\Models\PlayStationTrophy::query()
            ->where('is_earned', true)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->keyBy('type')
            ->map(fn ($r) => (int) $r->count);

        return [
            'totalEarned' => (int) $byType->sum(),
            'platinum'    => (int) ($byType->get('platinum') ?? 0),
            'gold'        => (int) ($byType->get('gold') ?? 0),
            'silver'      => (int) ($byType->get('silver') ?? 0),
            'bronze'      => (int) ($byType->get('bronze') ?? 0),
        ];
    }

    /** @return array<string, mixed> */
    private function consistencyStats(): array
    {
        $dates = $this->validSessionsBase()
            ->selectRaw('DATE(play_station_sessions.started_at) as day')
            ->groupByRaw('DATE(play_station_sessions.started_at)')
            ->orderByRaw('DATE(play_station_sessions.started_at)')
            ->pluck('day')
            ->map(fn ($d) => Carbon::parse($d)->startOfDay());

        if ($dates->isEmpty()) {
            return [
                'currentStreak'      => 0,
                'longestStreak'      => 0,
                'totalDays'          => 0,
                'avgSessionsPerWeek' => 0,
                'daysSinceLast'      => null,
            ];
        }

        // Longest streak
        $sorted = $dates->sort()->values();
        $longestStreak = 1;
        $running = 1;

        for ($i = 1; $i < $sorted->count(); $i++) {
            if ($sorted[$i - 1]->diffInDays($sorted[$i]) === 1) {
                $running++;
                $longestStreak = max($longestStreak, $running);
            } else {
                $running = 1;
            }
        }

        // Current streak (walk back from most recent day)
        $sortedDesc = $dates->sortDesc()->values();
        $lastDate   = $sortedDesc->first();
        $today      = now()->startOfDay();
        $currentStreak = 0;

        if ($lastDate->diffInDays($today) <= 1) {
            $currentStreak = 1;
            for ($i = 1; $i < $sortedDesc->count(); $i++) {
                if ($sortedDesc[$i - 1]->diffInDays($sortedDesc[$i]) === 1) {
                    $currentStreak++;
                } else {
                    break;
                }
            }
        }

        $longestStreak = max($longestStreak, $currentStreak);

        $totalSessionCount = $this->validSessionsBase()->count();
        $weeksSinceFirst   = max(1, (int) ceil($dates->first()->diffInDays(now()) / 7));
        $avgSessionsPerWeek = round($totalSessionCount / $weeksSinceFirst, 1);

        $lastSessionAt = $this->validSessionsBase()->max('play_station_sessions.started_at');
        $daysSinceLast = $lastSessionAt
            ? (int) Carbon::parse($lastSessionAt)->startOfDay()->diffInDays(now()->startOfDay())
            : null;

        return [
            'currentStreak'      => $currentStreak,
            'longestStreak'      => $longestStreak,
            'totalDays'          => $dates->count(),
            'avgSessionsPerWeek' => $avgSessionsPerWeek,
            'daysSinceLast'      => $daysSinceLast,
        ];
    }

    /** @return array<string, mixed> */
    private function yearInReview(): array
    {
        $year     = now()->year;
        $lastYear = $year - 1;

        $thisSessions = $this->validSessionsBase()
            ->whereYear('play_station_sessions.started_at', $year)
            ->get([
                'play_station_sessions.play_station_game_id',
                'play_station_sessions.duration_minutes',
                'play_station_sessions.started_at',
            ]);

        if ($thisSessions->isEmpty()) {
            return ['hasData' => false, 'year' => $year];
        }

        $thisMinutes      = (int) $thisSessions->sum('duration_minutes');
        $thisHours        = round($thisMinutes / 60, 1);
        $thisSessionCount = $thisSessions->count();
        $thisUniqueGames  = $thisSessions->pluck('play_station_game_id')->unique()->count();

        $byMonth = $thisSessions
            ->groupBy(fn ($s) => Carbon::parse($s->started_at)->month)
            ->map(fn ($group) => (int) $group->sum('duration_minutes'));

        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May',      6 => 'June',     7 => 'July',  8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        $bestMonthNum = $byMonth->sortDesc()->keys()->first();

        $newGamesThisYear = $this->validSessionsBase()
            ->selectRaw('play_station_sessions.play_station_game_id, MIN(play_station_sessions.started_at) as first_session')
            ->groupBy('play_station_sessions.play_station_game_id')
            ->havingRaw('YEAR(MIN(play_station_sessions.started_at)) = ?', [$year])
            ->count();

        $lastYearMinutes = (int) $this->validSessionsBase()
            ->whereYear('play_station_sessions.started_at', $lastYear)
            ->where('play_station_sessions.started_at', '<=', now()->subYear())
            ->sum('play_station_sessions.duration_minutes');

        $vsLastYear = $lastYearMinutes > 0
            ? round((($thisMinutes - $lastYearMinutes) / $lastYearMinutes) * 100, 1)
            : null;

        return [
            'hasData'        => true,
            'year'           => $year,
            'totalHours'     => $thisHours,
            'totalSessions'  => $thisSessionCount,
            'uniqueGames'    => $thisUniqueGames,
            'bestMonth'      => $bestMonthNum ? $monthNames[$bestMonthNum] : null,
            'bestMonthHours' => $bestMonthNum ? round((float) $byMonth[$bestMonthNum] / 60, 1) : null,
            'newGames'       => $newGamesThisYear,
            'vsLastYear'     => $vsLastYear,
        ];
    }

    private function validSessionsBase(): Builder
    {
        return PlayStationSession::query()
            ->join('play_station_games', 'play_station_sessions.play_station_game_id', '=', 'play_station_games.id')
            ->where(function (Builder $q): void {
                $q->whereNull('play_station_games.released_at')
                  ->orWhereColumn('play_station_sessions.started_at', '>=', 'play_station_games.released_at');
            });
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
}

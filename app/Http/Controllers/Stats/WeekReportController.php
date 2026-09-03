<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stats;

use App\Actions\Stats\BuildWeekReportAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

final class WeekReportController extends Controller
{
    public function __construct(
        private readonly BuildWeekReportAction $action,
    ) {}

    public function index(Request $request): View
    {
        $weekStart = $request->filled('week')
            ? Carbon::parse($request->get('week'))->startOfWeek(Carbon::MONDAY)
            : now()->startOfWeek(Carbon::MONDAY);

        // Don't allow future weeks
        if ($weekStart->isFuture()) {
            $weekStart = now()->startOfWeek(Carbon::MONDAY);
        }

        $report   = $this->action->handle($weekStart);
        $prevWeek = $weekStart->copy()->subWeek();
        $nextWeek = $weekStart->copy()->addWeek();

        return view('pages.stats.week', [
            'report'       => $report,
            'prevWeekUrl'  => route('stats.week', ['week' => $prevWeek->format('Y-m-d')]),
            'nextWeekUrl'  => $nextWeek->lte(now()) ? route('stats.week', ['week' => $nextWeek->format('Y-m-d')]) : null,
        ]);
    }
}

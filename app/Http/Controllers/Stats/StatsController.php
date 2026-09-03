<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stats;

use App\Actions\Stats\BuildHeatmapAction;
use App\Actions\Stats\BuildRecordsAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

final class StatsController extends Controller
{
    public function __construct(
        private readonly BuildHeatmapAction $heatmap,
        private readonly BuildRecordsAction $records,
    ) {}

    public function index(Request $request): View
    {
        $availableYears = $this->heatmap->availableYears();
        $currentYear    = now()->year;
        $year           = (int) $request->get('year', $currentYear);

        if (! in_array($year, $availableYears, strict: true)) {
            $year = $currentYear;
        }

        $start = Carbon::create($year, 1, 1)->startOfDay();
        $end   = $year === $currentYear
            ? now()->startOfDay()
            : Carbon::create($year, 12, 31)->endOfDay();

        return view('pages.stats.index', [
            'year'                 => $year,
            'availableYears'       => $availableYears,
            'stepsData'            => $this->heatmap->steps($start, $end),
            'gamingData'           => $this->heatmap->gaming($start, $end),
            'musicData'            => $this->heatmap->music($start, $end),
            'mediaData'            => $this->heatmap->media($start, $end),
            'recordSteps'          => $this->records->steps(),
            'recordGamingSession'  => $this->records->longestGamingSession(),
            'recordGamingDay'      => $this->records->mostGamingInADay(),
            'recordMusicDay'       => $this->records->mostTracksInADay(),
            'recordMediaDay'       => $this->records->mostMediaInADay(),
        ]);
    }
}

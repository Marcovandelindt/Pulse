<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stats;

use App\Actions\Stats\BuildHeatmapAction;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

final class StatsController extends Controller
{
    public function __construct(
        private readonly BuildHeatmapAction $heatmap,
    ) {}

    public function index(): View
    {
        return view('pages.stats.index', [
            'stepsData'  => $this->heatmap->steps(),
            'gamingData' => $this->heatmap->gaming(),
            'musicData'  => $this->heatmap->music(),
            'mediaData'  => $this->heatmap->media(),
        ]);
    }
}

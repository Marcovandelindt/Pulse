<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stats;

use App\Actions\Stats\BuildCrossStatsAction;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

final class CrossStatsController extends Controller
{
    public function __construct(private readonly BuildCrossStatsAction $action) {}

    public function index(): View
    {
        return view('pages.stats.patterns', [
            'stats' => $this->action->handle(),
        ]);
    }
}

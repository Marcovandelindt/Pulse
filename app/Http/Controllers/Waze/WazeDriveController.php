<?php

declare(strict_types=1);

namespace App\Http\Controllers\Waze;

use App\Http\Controllers\Controller;
use App\Models\WazeDrive;
use App\Queries\Waze\WazeStatsQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class WazeDriveController extends Controller
{
    public function __construct(
        private readonly WazeStatsQuery $stats,
    ) {}

    public function index(): View
    {
        $drives = WazeDrive::orderByDesc('driven_at')->paginate(25);

        return view('pages.waze.index', [
            'drives' => $drives,
            'stats' => $this->stats->handle(),
        ]);
    }

    public function destroyAll(): RedirectResponse
    {
        WazeDrive::truncate();

        return redirect()->route('waze.index')->with('success', 'All drives deleted.');
    }
}

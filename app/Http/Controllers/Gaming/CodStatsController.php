<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Http\Controllers\Controller;
use App\Queries\Gaming\CodStatsQuery;
use Illuminate\View\View;

final class CodStatsController extends Controller
{
    public function __construct(
        private readonly CodStatsQuery $query,
    ) {}

    public function index(): View
    {
        $stats = $this->query->handle();

        return view('pages.playstation.cod.stats', compact('stats'));
    }
}

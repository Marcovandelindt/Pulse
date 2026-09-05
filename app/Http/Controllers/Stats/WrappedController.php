<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stats;

use App\Actions\Stats\BuildWrappedAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class WrappedController extends Controller
{
    public function __construct(
        private readonly BuildWrappedAction $action,
    ) {}

    public function index(Request $request): View
    {
        $availableYears = $this->action->availableYears();
        $year           = (int) $request->get('year', now()->year);

        if (! in_array($year, $availableYears, strict: true)) {
            $year = now()->year;
        }

        return view('pages.stats.wrapped', [
            'wrapped'        => $this->action->handle($year),
            'availableYears' => $availableYears,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Http\Controllers\Controller;
use App\Queries\Gaming\PlayStationWrappedQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PlayStationWrappedController extends Controller
{
    public function __construct(
        private readonly PlayStationWrappedQuery $query,
    ) {}

    public function index(Request $request): View
    {
        $availableYears = $this->query->availableYears();
        $year           = (int) $request->get('year', now()->year);

        if (! in_array($year, $availableYears, strict: true) && ! empty($availableYears)) {
            $year = $availableYears[0];
        }

        return view('pages.playstation.wrapped', [
            'wrapped'        => $this->query->handle($year),
            'availableYears' => $availableYears,
        ]);
    }
}

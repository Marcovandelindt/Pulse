<?php

declare(strict_types=1);

namespace App\Http\Controllers\Gaming;

use App\Http\Controllers\Controller;
use App\Queries\Gaming\PlayStationMonthlyWrappedQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PlayStationMonthlyWrappedController extends Controller
{
    public function __construct(
        private readonly PlayStationMonthlyWrappedQuery $query,
    ) {}

    public function __invoke(Request $request): View
    {
        $availableMonths = $this->query->availableMonths();

        $defaultYear = $availableMonths[0]['year'] ?? now()->year;
        $defaultMonth = $availableMonths[0]['month'] ?? now()->month;

        $year = (int) $request->get('year', $defaultYear);
        $month = (int) $request->get('month', $defaultMonth);

        $keys = array_column($availableMonths, 'key');
        $current = $year.'-'.str_pad((string) $month, 2, '0', STR_PAD_LEFT);

        if (! in_array($current, $keys, strict: true) && ! empty($availableMonths)) {
            $year = $availableMonths[0]['year'];
            $month = $availableMonths[0]['month'];
        }

        $index = (int) array_search($year.'-'.str_pad((string) $month, 2, '0', STR_PAD_LEFT), $keys, strict: true);

        $prevMonth = $index < count($availableMonths) - 1 ? $availableMonths[$index + 1] : null;
        $nextMonth = $index > 0 ? $availableMonths[$index - 1] : null;

        return view('pages.playstation.monthly-wrapped', [
            'wrapped' => $this->query->handle($year, $month),
            'availableMonths' => $availableMonths,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
        ]);
    }
}

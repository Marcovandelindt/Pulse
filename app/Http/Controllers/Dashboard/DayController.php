<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Actions\Dashboard\BuildDayView;
use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

final class DayController extends Controller
{
    public function __construct(
        private readonly BuildDayView $buildDayView,
    ) {}

    public function show(?string $date = null): View
    {
        $carbon = $date !== null ? Carbon::parse($date)->startOfDay() : Carbon::today();

        return view('pages.day.show', $this->buildDayView->handle($carbon));
    }
}

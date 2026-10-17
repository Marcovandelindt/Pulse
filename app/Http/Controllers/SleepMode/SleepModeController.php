<?php

declare(strict_types=1);

namespace App\Http\Controllers\SleepMode;

use App\Http\Controllers\Controller;
use App\Queries\SleepMode\GlobalSleepModeQuery;
use Illuminate\View\View;

final class SleepModeController extends Controller
{
    public function index(GlobalSleepModeQuery $query): View
    {
        return view('pages.sleep-mode.index', $query->get());
    }
}

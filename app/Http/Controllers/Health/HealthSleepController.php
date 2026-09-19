<?php

declare(strict_types=1);

namespace App\Http\Controllers\Health;

use App\Http\Controllers\Controller;
use App\Models\HealthSleep;
use Illuminate\View\View;

final class HealthSleepController extends Controller
{
    public function index(): View
    {
        $records = HealthSleep::orderByDesc('date')->get();

        $lastSleep = $records->first();
        $avgTotal  = $records->avg('total_sleep_minutes');
        $avgDeep   = $records->avg('deep_minutes');
        $avgRem    = $records->avg('rem_minutes');
        $avgCore   = $records->avg('core_minutes');
        $avgScore  = $records->isEmpty() ? null : (int) round($records->avg(fn ($r) => $r->sleepScore()));

        return view('pages.health.sleep', compact(
            'records', 'lastSleep', 'avgTotal', 'avgDeep', 'avgRem', 'avgCore', 'avgScore',
        ));
    }
}

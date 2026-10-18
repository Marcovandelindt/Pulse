<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\HealthSleep;

final class GetCurrentSleepStreak
{
    public function handle(): int
    {
        $records = HealthSleep::orderByDesc('date')->take(60)->get(['date', 'total_sleep_minutes']);

        if ($records->isEmpty()) {
            return 0;
        }

        if ($records->first()->date->copy()->startOfDay()->lt(now()->subDay()->startOfDay())) {
            return 0;
        }

        $streak = 0;
        $prevDate = null;

        foreach ($records as $record) {
            if ($record->total_sleep_minutes < 420) {
                break;
            }
            if ($prevDate !== null && ! $record->date->copy()->addDay()->startOfDay()->equalTo($prevDate)) {
                break;
            }

            $streak++;
            $prevDate = $record->date->copy()->startOfDay();
        }

        return $streak;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\HealthEntry;
use App\Models\HealthSleep;

final class BuildDailyBrief
{
    /**
     * @return array<string, mixed>
     */
    public function handle(int $todaySteps, int $stepGoal, ?HealthSleep $lastSleep): array
    {
        $sevenDayAvg = (int) round(
            (float) (HealthEntry::withSteps()
                ->where('date', '>=', now()->subDays(7)->startOfDay())
                ->where('date', '<', today())
                ->avg('steps') ?? 0)
        );

        $goalPct = $stepGoal > 0 ? min(100, (int) round($todaySteps / $stepGoal * 100)) : null;
        $remaining = $stepGoal > 0 ? max(0, $stepGoal - $todaySteps) : null;

        $stepVsAvg = null;
        if ($sevenDayAvg > 0 && $todaySteps > 0) {
            $stepVsAvg = (int) round(($todaySteps - $sevenDayAvg) / $sevenDayAvg * 100);
        }

        return [
            'sleep' => $lastSleep && $lastSleep->date->copy()->gte(today()->subDay())
                ? [
                    'duration' => $lastSleep->formattedMinutes($lastSleep->total_sleep_minutes),
                    'score' => $lastSleep->sleepScore(),
                    'label' => $lastSleep->sleepScoreLabel(),
                ]
                : null,
            'steps' => $todaySteps > 0 ? number_format($todaySteps) : null,
            'stepGoal' => $stepGoal > 0 ? number_format($stepGoal) : null,
            'goalPct' => $goalPct,
            'remaining' => $remaining !== null && $remaining > 0 ? number_format($remaining) : null,
            'sevenDayAvg' => $sevenDayAvg > 0 ? number_format($sevenDayAvg) : null,
            'stepVsAvg' => $stepVsAvg,
        ];
    }
}

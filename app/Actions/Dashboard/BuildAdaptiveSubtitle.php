<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\HealthSleep;

final class BuildAdaptiveSubtitle
{
    public function handle(int $todaySteps, int $stepGoal, ?HealthSleep $lastSleep): string
    {
        $hour = now()->hour;
        $parts = [];

        if ($lastSleep && $hour < 14) {
            $parts[] = 'Slept '.$lastSleep->formattedMinutes($lastSleep->total_sleep_minutes).' last night';
        }

        if ($stepGoal > 0) {
            if ($todaySteps >= $stepGoal) {
                $parts[] = 'Goal hit · '.number_format($todaySteps).' steps today';
            } elseif ($todaySteps > 0) {
                $parts[] = number_format($stepGoal - $todaySteps).' steps to go today';
            } elseif ($hour >= 9) {
                $parts[] = 'Goal: '.number_format($stepGoal).' steps today';
            }
        } elseif ($todaySteps > 0) {
            $parts[] = number_format($todaySteps).' steps today';
        }

        return implode(' · ', $parts) ?: "Here's what's happening today.";
    }
}

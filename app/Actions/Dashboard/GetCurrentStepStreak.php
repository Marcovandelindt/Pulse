<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\HealthEntry;
use App\Models\StepGoal;

final class GetCurrentStepStreak
{
    public function handle(): int
    {
        $goals = StepGoal::orderByDesc('effective_from')->get();
        $entries = HealthEntry::withSteps()->orderByDesc('date')->take(90)->get(['date', 'steps']);

        if ($entries->isEmpty()) {
            return 0;
        }

        $todayLogged = $entries->first()->date->copy()->startOfDay()->equalTo(now()->startOfDay());
        $check = $todayLogged ? now()->startOfDay() : now()->subDay()->startOfDay();

        while ($check->isWeekend()) {
            $check->subDay();
        }

        $streak = 0;

        foreach ($entries as $entry) {
            if ($entry->date->isWeekend()) {
                continue;
            }
            if (! $entry->date->copy()->startOfDay()->equalTo($check)) {
                break;
            }

            $goalEntry = $goals->first(fn (StepGoal $g) => ! $g->effective_from->isAfter($entry->date));
            $goal = $goalEntry !== null ? $goalEntry->steps : 10000;
            if (($entry->steps ?? 0) < $goal) {
                break;
            }

            $streak++;
            $check->subDay();
            while ($check->isWeekend()) {
                $check->subDay();
            }
        }

        return $streak;
    }
}

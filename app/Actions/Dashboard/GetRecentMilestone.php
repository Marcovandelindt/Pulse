<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\HealthEntry;
use App\Models\Play;
use App\Models\PlayStationSession;

final class GetRecentMilestone
{
    /**
     * @return array<string, mixed>|null
     */
    public function handle(int $stepStreak, int $sleepStreak): ?array
    {
        $candidates = [];

        foreach ([7, 14, 30, 60, 100] as $target) {
            if ($stepStreak === $target) {
                $candidates[] = ['priority' => 3, 'icon' => '🔥', 'title' => "{$target}-day step streak!", 'sub' => "You've hit your step goal {$target} weekdays in a row."];
            }
            if ($sleepStreak === $target) {
                $candidates[] = ['priority' => 3, 'icon' => '😴', 'title' => "{$target}-night sleep streak!", 'sub' => "{$target} consecutive nights of 7+ hours sleep."];
            }
        }

        $totalPlays = Play::count();
        $last7Plays = Play::where('played_at', '>=', now()->subDays(7))->count();
        $playsWeekAgo = $totalPlays - $last7Plays;

        foreach ([500, 1000, 2000, 5000, 10000, 25000, 50000] as $m) {
            if ($playsWeekAgo < $m && $totalPlays >= $m) {
                $candidates[] = ['priority' => 2, 'icon' => '🎵', 'title' => number_format($m).' tracks played!', 'sub' => "You've listened to {$m} songs on Spotify."];
                break;
            }
        }

        $totalGamingMinutes = (int) PlayStationSession::sum('duration_minutes');
        $last7GamingMinutes = (int) PlayStationSession::where('started_at', '>=', now()->subDays(7))->sum('duration_minutes');
        $gamingMinutesWeekAgo = $totalGamingMinutes - $last7GamingMinutes;

        foreach ([3000, 6000, 12000, 30000, 60000] as $m) {
            if ($gamingMinutesWeekAgo < $m && $totalGamingMinutes >= $m) {
                $hours = intdiv($m, 60);
                $candidates[] = ['priority' => 2, 'icon' => '🎮', 'title' => "{$hours} hours gamed!", 'sub' => "You've clocked {$hours} hours on PlayStation."];
                break;
            }
        }

        $totalSteps = (int) HealthEntry::withSteps()->sum('steps');
        $last7StepsSum = (int) HealthEntry::withSteps()->where('date', '>=', now()->subDays(7))->sum('steps');
        $stepsWeekAgo = $totalSteps - $last7StepsSum;

        foreach ([500000, 1000000, 2000000, 5000000, 10000000] as $m) {
            if ($stepsWeekAgo < $m && $totalSteps >= $m) {
                $label = number_format($m / 1000000, 1).'M';
                $candidates[] = ['priority' => 1, 'icon' => '🚶', 'title' => "{$label} steps total!", 'sub' => 'A life milestone in total steps recorded.'];
                break;
            }
        }

        if (empty($candidates)) {
            return null;
        }

        usort($candidates, fn ($a, $b) => $b['priority'] <=> $a['priority']);

        return $candidates[0];
    }
}

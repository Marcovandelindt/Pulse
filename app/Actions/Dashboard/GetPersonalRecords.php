<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\HealthEntry;
use App\Models\HealthSleep;
use App\Models\Play;
use App\Models\PlayStationSession;
use Illuminate\Support\Carbon;

final class GetPersonalRecords
{
    /**
     * @return array<int, array<string, string>>
     */
    public function handle(): array
    {
        $records = [];

        $bestSteps = HealthEntry::withSteps()->orderByDesc('steps')->first(['steps', 'date']);
        if ($bestSteps && $bestSteps->steps > 0) {
            $records[] = [
                'icon' => '🚶',
                'label' => 'Best step day',
                'value' => number_format((int) $bestSteps->steps).' steps',
                'date' => $bestSteps->date->format('d M Y'),
            ];
        }

        $longestSession = PlayStationSession::with('game')->orderByDesc('duration_minutes')->first();
        if ($longestSession) {
            $h = intdiv($longestSession->duration_minutes, 60);
            $m = $longestSession->duration_minutes % 60;
            $duration = $h > 0 ? "{$h}h".($m > 0 ? " {$m}m" : '') : "{$m}m";
            $records[] = [
                'icon' => '🎮',
                'label' => 'Longest session',
                'value' => $duration.' — '.$longestSession->game->label,
                'date' => $longestSession->started_at->format('d M Y'),
            ];
        }

        $bestSleep = HealthSleep::orderByDesc('total_sleep_minutes')->first(['total_sleep_minutes', 'date']);
        if ($bestSleep && $bestSleep->total_sleep_minutes > 0) {
            $records[] = [
                'icon' => '😴',
                'label' => 'Best sleep',
                'value' => $bestSleep->formattedMinutes($bestSleep->total_sleep_minutes),
                'date' => $bestSleep->date->format('d M Y'),
            ];
        }

        $bestMusicDay = Play::selectRaw('DATE(played_at) as day, COUNT(*) as track_count')
            ->whereNotNull('played_at')
            ->groupBy('day')
            ->orderByDesc('track_count')
            ->first();
        $trackCount = (int) $bestMusicDay?->getAttribute('track_count');
        if ($bestMusicDay && $trackCount > 0) {
            $records[] = [
                'icon' => '🎵',
                'label' => 'Most tracks in a day',
                'value' => number_format($trackCount).' tracks',
                'date' => Carbon::parse((string) $bestMusicDay->getAttribute('day'))->format('d M Y'),
            ];
        }

        return $records;
    }
}

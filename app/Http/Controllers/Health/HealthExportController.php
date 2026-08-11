<?php

declare(strict_types=1);

namespace App\Http\Controllers\Health;

use App\Http\Controllers\Controller;
use App\Models\HealthEntry;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class HealthExportController extends Controller
{
    public function index(): StreamedResponse
    {
        $entries = HealthEntry::query()->orderBy('date')->get();
        $goal = HealthEntry::stepGoal();

        $start = $entries->first()?->date->format('Y-m-d') ?? now()->format('Y-m-d');
        $end = $entries->last()?->date->format('Y-m-d') ?? now()->format('Y-m-d');

        return response()->streamDownload(function () use ($entries, $goal) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['date', 'day_of_week', 'steps', 'goal', 'goal_met', 'notes']);

            foreach ($entries as $entry) {
                fputcsv($handle, [
                    $entry->date->format('Y-m-d'),
                    $entry->date->format('l'),
                    $entry->steps ?? '',
                    $goal,
                    $entry->meetsStepGoal() ? 'yes' : 'no',
                    $entry->notes ?? '',
                ]);
            }

            fclose($handle);
        }, "health-steps-{$start}-to-{$end}.csv", [
            'Content-Type' => 'text/csv',
        ]);
    }
}

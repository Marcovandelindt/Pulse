<?php

declare(strict_types=1);

namespace App\Services\Nintendo;

use Illuminate\Support\Facades\Process;
use RuntimeException;

final class NintendoService
{
    /**
     * Fetch daily play records via the nxapi CLI.
     *
     * Run `nxapi pctl --help` to verify available commands and adjust if needed.
     * Expected JSON structure per item:
     *   {
     *     "applicationId": "0100000000010000",
     *     "applicationName": "Game Title",
     *     "imageUrl": "https://...",
     *     "playingTime": 120,   // minutes
     *     "date": "2024-01-15"
     *   }
     *
     * @return array<int, array{applicationId: string, applicationName: string, imageUrl: string|null, playingTime: int, date: string}>
     */
    public function fetchDailySummaries(): array
    {
        $result = Process::run(['nxapi', 'pctl', 'daily-summaries', '--json']);

        if (! $result->successful()) {
            throw new RuntimeException('nxapi pctl daily-summaries failed: ' . $result->errorOutput());
        }

        $decoded = json_decode($result->output(), true);

        if (! is_array($decoded)) {
            throw new RuntimeException('nxapi returned unexpected output: ' . $result->output());
        }

        return $decoded;
    }

    public function isAvailable(): bool
    {
        $result = Process::run(['nxapi', '--version']);

        return $result->successful();
    }
}

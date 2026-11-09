<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LocationPoint;
use App\Models\LocationTrip;
use App\Models\LocationVisit;
use App\Services\Location\LocationProcessorService;
use Illuminate\Console\Command;

final class ReprocessLocationCommand extends Command
{
    protected $signature = 'location:reprocess
        {--device= : Only reprocess points for this device_id}';

    protected $description = 'Delete all visits and trips and reprocess from raw location_points';

    public function handle(LocationProcessorService $processor): int
    {
        $device = $this->option('device');

        $this->info('Deleting existing visits and trips…');

        $visitQuery = LocationVisit::query();
        $tripQuery = LocationTrip::query();

        if (is_string($device)) {
            $visitQuery->where('device_id', $device);
            $tripQuery->where('device_id', $device);
        }

        $visitQuery->delete();
        $tripQuery->delete();

        // Find all device_ids to process
        $deviceQuery = LocationPoint::query()->select('device_id')->distinct();

        if (is_string($device)) {
            $deviceQuery->where('device_id', $device);
        }

        $devices = $deviceQuery->pluck('device_id');

        if ($devices->isEmpty()) {
            $this->warn('No location points found.');

            return self::SUCCESS;
        }

        foreach ($devices as $deviceId) {
            $baseQuery = LocationPoint::where('device_id', $deviceId);
            $minAt = (clone $baseQuery)->min('recorded_at');
            $maxAt = (clone $baseQuery)->max('recorded_at');

            if (! is_string($minAt) || ! is_string($maxAt)) {
                continue;
            }

            $from = now()->parse($minAt);
            $to = now()->parse($maxAt);

            $this->line("Processing device <fg=cyan>{$deviceId}</> ({$from->toDateString()} → {$to->toDateString()})…");

            $processor->processForDevice($deviceId, $from, $to);
        }

        $visits = LocationVisit::count();
        $trips = LocationTrip::count();

        $this->info("Done. Created {$visits} visit(s) and {$trips} trip(s).");

        return self::SUCCESS;
    }
}

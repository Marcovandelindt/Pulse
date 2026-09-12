<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\HealthEntry;
use App\Models\HealthSleep;
use Carbon\Carbon;
use Illuminate\Console\Command;

final class ImportAppleHealth extends Command
{
    protected $signature = 'health:import-icloud
                            {--path= : Override the configured iCloud directory path}';

    protected $description = 'Import Apple Health data from a Health Auto Export JSON file';

    private const KJ_TO_KCAL = 0.239005736;

    public function handle(): int
    {
        $dir = $this->option('path') ?? config('health.icloud_path');

        if (! $dir) {
            $this->error('No path configured. Set HEALTH_ICLOUD_PATH in .env or pass --path=');
            return self::FAILURE;
        }

        $path = $this->resolveFile($dir);

        if (! $path) {
            $this->error("No HealthAutoExport-*.json file found in: {$dir}");
            return self::FAILURE;
        }

        $this->info('Importing ' . basename($path) . '...');

        $json = json_decode(file_get_contents($path), true);

        if (! isset($json['data']['metrics'])) {
            $this->error('Unexpected JSON structure — missing data.metrics');
            return self::FAILURE;
        }

        $index = $this->buildIndex($json['data']['metrics']);

        [$created, $updated] = $this->importEntries($index);
        $this->info("Health entries: {$created} created, {$updated} updated.");

        $sleepCount = $this->importSleep($index['sleep_analysis'] ?? []);
        $this->info("Sleep records: {$sleepCount} imported.");

        return self::SUCCESS;
    }

    private function resolveFile(string $dir): ?string
    {
        // If the path points directly to a file, use it as-is
        if (is_file($dir)) {
            return $dir;
        }

        $files = glob(rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'HealthAutoExport-*.json');

        if (empty($files)) {
            return null;
        }

        // Pick the most recently modified file
        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));

        return $files[0];
    }

    /** @param array<int, array<string, mixed>> $metrics */
    private function buildIndex(array $metrics): array
    {
        $index = [];

        foreach ($metrics as $metric) {
            $name = $metric['name'];
            foreach ($metric['data'] as $entry) {
                $date = Carbon::parse($entry['date'])->toDateString();
                $index[$name][$date] = $entry;
            }
        }

        return $index;
    }

    /** @return array{int, int} [created, updated] */
    private function importEntries(array $index): array
    {
        $dates = collect($index)
            ->except('sleep_analysis')
            ->flatMap(fn ($byDate) => array_keys($byDate))
            ->unique()
            ->sort()
            ->values();

        $created = $updated = 0;

        foreach ($dates as $date) {
            $data = array_filter([
                'steps'               => $this->qty($index, 'step_count', $date, fn ($v) => (int) $v),
                'active_calories'     => $this->qty($index, 'active_energy', $date, fn ($v) => (int) round($v * self::KJ_TO_KCAL)),
                'basal_calories'      => $this->qty($index, 'basal_energy_burned', $date, fn ($v) => (int) round($v * self::KJ_TO_KCAL)),
                'heart_rate_avg'      => isset($index['heart_rate'][$date]['Avg']) ? (int) round($index['heart_rate'][$date]['Avg']) : null,
                'heart_rate_min'      => isset($index['heart_rate'][$date]['Min']) ? (int) round($index['heart_rate'][$date]['Min']) : null,
                'heart_rate_max'      => isset($index['heart_rate'][$date]['Max']) ? (int) round($index['heart_rate'][$date]['Max']) : null,
                'resting_heart_rate'  => $this->qty($index, 'resting_heart_rate', $date, fn ($v) => (int) round($v)),
                'hrv'                 => $this->qty($index, 'heart_rate_variability', $date, fn ($v) => round($v, 1)),
                'respiratory_rate'    => $this->qty($index, 'respiratory_rate', $date, fn ($v) => round($v, 1)),
                'exercise_minutes'    => $this->qty($index, 'apple_exercise_time', $date, fn ($v) => (int) round($v)),
                'stand_hours'         => $this->qty($index, 'apple_stand_hour', $date, fn ($v) => (int) $v),
                'flights_climbed'              => $this->qty($index, 'flights_climbed', $date, fn ($v) => (int) $v),
                'distance_km'                 => $this->qty($index, 'walking_running_distance', $date, fn ($v) => round($v, 2)),
                'weight_kg'                   => $this->qty($index, 'weight_body_mass', $date, fn ($v) => round($v, 1)),
                // Mobility
                'walking_speed_kmh'           => $this->qty($index, 'walking_speed', $date, fn ($v) => round($v, 2)),
                'walking_step_length_cm'      => $this->qty($index, 'walking_step_length', $date, fn ($v) => round($v, 2)),
                'walking_asymmetry_pct'       => $this->qty($index, 'walking_asymmetry_percentage', $date, fn ($v) => round($v, 2)),
                'walking_double_support_pct'  => $this->qty($index, 'walking_double_support_percentage', $date, fn ($v) => round($v, 2)),
                'stair_speed_up'              => $this->qty($index, 'stair_speed_up', $date, fn ($v) => round($v, 3)),
                'stair_speed_down'            => $this->qty($index, 'stair_speed_down', $date, fn ($v) => round($v, 3)),
                'time_in_daylight_minutes'    => $this->qty($index, 'time_in_daylight', $date, fn ($v) => (int) round($v)),
                'walking_heart_rate_avg'      => $this->qty($index, 'walking_heart_rate_average', $date, fn ($v) => round($v, 1)),
                // Hearing
                'headphone_audio_exposure_db'     => $this->qty($index, 'headphone_audio_exposure', $date, fn ($v) => round($v, 2)),
                'environmental_audio_exposure_db' => $this->qty($index, 'environmental_audio_exposure', $date, fn ($v) => round($v, 2)),
            ], fn ($v) => $v !== null);

            if (empty($data)) {
                continue;
            }

            $entry = HealthEntry::firstOrNew(['date' => $date]);
            $entry->fill($data);

            if ($entry->exists) {
                $entry->save();
                $updated++;
            } else {
                $entry->save();
                $created++;
            }
        }

        return [$created, $updated];
    }

    private function importSleep(array $sleepByDate): int
    {
        $count = 0;

        foreach ($sleepByDate as $entry) {
            HealthSleep::updateOrCreate(
                ['sleep_start' => Carbon::parse($entry['sleepStart'])],
                [
                    'date'                => Carbon::parse($entry['date'])->toDateString(),
                    'sleep_end'           => Carbon::parse($entry['sleepEnd']),
                    'in_bed_start'        => isset($entry['inBedStart']) ? Carbon::parse($entry['inBedStart']) : null,
                    'in_bed_end'          => isset($entry['inBedEnd']) ? Carbon::parse($entry['inBedEnd']) : null,
                    'total_sleep_minutes' => (int) round($entry['totalSleep'] * 60),
                    'awake_minutes'       => isset($entry['awake']) ? (int) round($entry['awake'] * 60) : null,
                    'rem_minutes'         => isset($entry['rem']) ? (int) round($entry['rem'] * 60) : null,
                    'deep_minutes'        => isset($entry['deep']) ? (int) round($entry['deep'] * 60) : null,
                    'core_minutes'        => isset($entry['core']) ? (int) round($entry['core'] * 60) : null,
                    'source'              => $entry['source'] ?? null,
                ]
            );
            $count++;
        }

        return $count;
    }

    private function qty(array $index, string $metric, string $date, callable $transform): mixed
    {
        $qty = $index[$metric][$date]['qty'] ?? null;
        return $qty !== null ? $transform($qty) : null;
    }
}

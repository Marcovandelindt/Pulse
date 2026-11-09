<?php

declare(strict_types=1);

namespace App\Actions\Location;

use App\Models\LocationPoint;
use Illuminate\Support\Facades\DB;

final class StoreLocationBatch
{
    private const int CHUNK_SIZE = 500;

    /** @param list<array<string, mixed>> $features */
    public function handle(array $features): LocationBatchResult
    {
        $rows = [];
        $ranges = [];

        foreach ($features as $feature) {
            $data = LocationPointData::fromGeoJsonFeature($feature);
            $rows[] = $this->toRow($data);

            $device = $data->deviceId;
            if (! isset($ranges[$device])) {
                $ranges[$device] = ['from' => $data->recordedAt->copy(), 'to' => $data->recordedAt->copy()];
            } else {
                if ($data->recordedAt < $ranges[$device]['from']) {
                    $ranges[$device]['from'] = $data->recordedAt->copy();
                }
                if ($data->recordedAt > $ranges[$device]['to']) {
                    $ranges[$device]['to'] = $data->recordedAt->copy();
                }
            }
        }

        $inserted = 0;

        DB::transaction(function () use ($rows, &$inserted): void {
            foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
                $inserted += LocationPoint::insertOrIgnore($chunk);
            }
        });

        $deviceRanges = array_map(
            fn (string $deviceId, array $range) => new DeviceBatchRange($deviceId, $range['from'], $range['to']),
            array_keys($ranges),
            $ranges,
        );

        return new LocationBatchResult($inserted, $deviceRanges);
    }

    /** @return array<string, mixed> */
    private function toRow(LocationPointData $data): array
    {
        return [
            'device_id' => $data->deviceId,
            'recorded_at' => $data->recordedAt->toDateTimeString(),
            'latitude' => $data->latitude,
            'longitude' => $data->longitude,
            'altitude' => $data->altitude,
            'speed' => $data->speed,
            'horizontal_accuracy' => $data->horizontalAccuracy,
            'motion' => $data->motion !== null ? json_encode($data->motion) : null,
            'battery_level' => $data->batteryLevel,
            'raw' => json_encode($data->raw),
        ];
    }
}

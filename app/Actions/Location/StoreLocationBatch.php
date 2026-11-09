<?php

declare(strict_types=1);

namespace App\Actions\Location;

use App\Models\LocationPoint;
use Illuminate\Support\Facades\DB;

final class StoreLocationBatch
{
    private const int CHUNK_SIZE = 500;

    /** @param list<array<string, mixed>> $features */
    public function handle(array $features): int
    {
        $rows = array_map(
            fn (array $feature) => $this->toRow(LocationPointData::fromGeoJsonFeature($feature)),
            $features,
        );

        $inserted = 0;

        DB::transaction(function () use ($rows, &$inserted): void {
            foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
                $inserted += LocationPoint::insertOrIgnore($chunk);
            }
        });

        return $inserted;
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

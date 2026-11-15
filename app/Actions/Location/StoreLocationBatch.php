<?php

declare(strict_types=1);

namespace App\Actions\Location;

use App\Models\LocationPoint;
use Illuminate\Support\Facades\DB;

final class StoreLocationBatch
{
    public function handle(LocationPointData $data): LocationBatchResult
    {
        $inserted = 0;

        DB::transaction(function () use ($data, &$inserted): void {
            $inserted += LocationPoint::insertOrIgnore([$this->toRow($data)]);
        });

        $deviceRanges = [new DeviceBatchRange($data->deviceId, $data->recordedAt->copy(), $data->recordedAt->copy())];

        return new LocationBatchResult($inserted, $deviceRanges);
    }

    /** @return array<string, mixed> */
    private function toRow(LocationPointData $data): array
    {
        return [
            'device_id'          => $data->deviceId,
            'recorded_at'        => $data->recordedAt->toDateTimeString(),
            'latitude'           => $data->latitude,
            'longitude'          => $data->longitude,
            'altitude'           => $data->altitude,
            'speed'              => $data->speed,
            'horizontal_accuracy' => $data->horizontalAccuracy,
            'motion'             => $data->motion !== null ? json_encode($data->motion) : null,
            'battery_level'      => $data->batteryLevel,
            'raw'                => json_encode($data->raw),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Location;

use Carbon\Carbon;

final readonly class LocationPointData
{
    /**
     * @param  array<int, string>|null  $motion
     * @param  array<string, mixed>     $raw
     */
    public function __construct(
        public string $deviceId,
        public Carbon $recordedAt,
        public float $latitude,
        public float $longitude,
        public ?float $altitude,
        public ?float $speed,
        public ?float $horizontalAccuracy,
        public ?array $motion,
        public ?float $batteryLevel,
        public array $raw,
    ) {}

    /** @param array<string, mixed> $feature */
    public static function fromGeoJsonFeature(array $feature): self
    {
        // GeoJSON coordinates are [longitude, latitude]
        $coordinates = $feature['geometry']['coordinates'];
        $properties = $feature['properties'];

        return new self(
            deviceId: (string) ($properties['device_id'] ?? ''),
            recordedAt: Carbon::parse($properties['timestamp']),
            latitude: (float) $coordinates[1],
            longitude: (float) $coordinates[0],
            altitude: isset($properties['altitude']) ? (float) $properties['altitude'] : null,
            speed: isset($properties['speed']) ? (float) $properties['speed'] : null,
            horizontalAccuracy: isset($properties['horizontal_accuracy']) ? (float) $properties['horizontal_accuracy'] : null,
            motion: isset($properties['motion'])
                ? array_values(array_map(strval(...), (array) $properties['motion']))
                : null,
            batteryLevel: isset($properties['battery_level']) ? (float) $properties['battery_level'] : null,
            raw: $properties,
        );
    }
}

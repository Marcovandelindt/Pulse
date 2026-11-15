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

    /** @param array<string, mixed> $payload */
    public static function fromOwnTracks(array $payload, string $deviceId): self
    {
        $velKmh = isset($payload['vel']) ? (float) $payload['vel'] : null;

        // OwnTracks vel is km/h; store as m/s for consistency with the rest of the pipeline
        $speedMs = $velKmh !== null ? $velKmh / 3.6 : null;

        // Infer motion type from velocity since OwnTracks does not expose CoreMotion
        $motion = match (true) {
            $velKmh === null  => null,
            $velKmh < 2.0    => ['stationary'],
            $velKmh < 10.0   => ['walking'],
            $velKmh < 30.0   => ['cycling'],
            default          => ['driving'],
        };

        // OwnTracks batt is 0–100; normalise to 0.0–1.0
        $batteryLevel = isset($payload['batt']) ? (float) $payload['batt'] / 100.0 : null;

        return new self(
            deviceId: $deviceId,
            recordedAt: Carbon::createFromTimestamp((int) $payload['tst']),
            latitude: (float) $payload['lat'],
            longitude: (float) $payload['lon'],
            altitude: isset($payload['alt']) ? (float) $payload['alt'] : null,
            speed: $speedMs,
            horizontalAccuracy: isset($payload['acc']) ? (float) $payload['acc'] : null,
            motion: $motion,
            batteryLevel: $batteryLevel,
            raw: $payload,
        );
    }
}

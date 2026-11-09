<?php

declare(strict_types=1);

use App\Models\LocationPoint;
use App\Models\LocationTrip;
use App\Models\LocationVisit;
use App\Models\Place;
use App\Services\Location\LocationProcessorService;
use Carbon\Carbon;

/**
 * Helper: create a LocationPoint row directly in the DB.
 *
 * @param  array<int, string>|null  $motion
 */
function makePoint(
    float $lat,
    float $lon,
    string $recordedAt,
    string $deviceId = 'test-device',
    ?float $speed = null,
    ?array $motion = null,
    ?float $accuracy = 5.0,
): LocationPoint {
    return LocationPoint::create([
        'device_id' => $deviceId,
        'recorded_at' => $recordedAt,
        'latitude' => $lat,
        'longitude' => $lon,
        'speed' => $speed,
        'horizontal_accuracy' => $accuracy,
        'motion' => $motion,
        'raw' => json_encode([]),
    ]);
}

// Helper: create a cluster of N points near the given coordinates
function makeCluster(
    float $lat,
    float $lon,
    string $startAt,
    int $count = 5,
    int $intervalMinutes = 3,
    string $deviceId = 'test-device',
): void {
    $time = Carbon::parse($startAt);
    for ($i = 0; $i < $count; $i++) {
        // Offset by ~10 m so points aren't identical but stay within visit radius
        makePoint($lat + ($i * 0.00005), $lon, $time->toDateTimeString(), $deviceId);
        $time->addMinutes($intervalMinutes);
    }
}

beforeEach(function (): void {
    config([
        'location.processing.visit_radius_meters' => 100,
        'location.processing.min_visit_duration_minutes' => 10,
        'location.processing.place_merge_radius_meters' => 150,
        'location.processing.max_accuracy_meters' => 100,
        'location.processing.trip_polyline_max_points' => 300,
        'location.processing.processing_buffer_hours' => 0,
    ]);
});

it('detects a stationary cluster as a visit', function (): void {
    // 5 points over 12 minutes, all near 52.37, 4.89
    makeCluster(52.370, 4.890, '2026-11-09 10:00:00', count: 5, intervalMinutes: 3);

    $processor = app(LocationProcessorService::class);
    $processor->processForDevice('test-device', Carbon::parse('2026-11-09 09:00:00'), Carbon::parse('2026-11-09 11:00:00'));

    expect(LocationVisit::count())->toBe(1)
        ->and(LocationTrip::count())->toBe(0);
});

it('does not create a visit for a cluster shorter than min duration', function (): void {
    // 3 points over 4 minutes — below the 10 min threshold
    makeCluster(52.370, 4.890, '2026-11-09 10:00:00', count: 3, intervalMinutes: 2);

    $processor = app(LocationProcessorService::class);
    $processor->processForDevice('test-device', Carbon::parse('2026-11-09 09:00:00'), Carbon::parse('2026-11-09 11:00:00'));

    expect(LocationVisit::count())->toBe(0);
});

it('detects movement between two visits as a trip', function (): void {
    // Visit A: 10:00–10:12 near 52.37, 4.89
    makeCluster(52.370, 4.890, '2026-11-09 10:00:00', count: 5, intervalMinutes: 3);

    // Travel points: 10:15–10:25 (moving far from both visits)
    makePoint(52.380, 4.900, '2026-11-09 10:15:00', speed: 15.0, motion: ['driving']);
    makePoint(52.390, 4.910, '2026-11-09 10:20:00', speed: 14.0, motion: ['driving']);
    makePoint(52.400, 4.920, '2026-11-09 10:25:00', speed: 13.0, motion: ['driving']);

    // Visit B: 10:30–10:42 near 52.41, 4.93
    makeCluster(52.410, 4.930, '2026-11-09 10:30:00', count: 5, intervalMinutes: 3);

    $processor = app(LocationProcessorService::class);
    $processor->processForDevice('test-device', Carbon::parse('2026-11-09 09:00:00'), Carbon::parse('2026-11-09 12:00:00'));

    expect(LocationVisit::count())->toBe(2)
        ->and(LocationTrip::count())->toBe(1);

    $trip = LocationTrip::firstOrFail();
    expect($trip->dominant_motion)->toBe('driving')
        ->and($trip->distance_meters)->toBeGreaterThan(0.0)
        ->and($trip->point_count)->toBe(3);
});

it('stores a polyline on the trip', function (): void {
    makeCluster(52.370, 4.890, '2026-11-09 10:00:00', count: 5, intervalMinutes: 3);
    makePoint(52.380, 4.900, '2026-11-09 10:15:00');
    makePoint(52.390, 4.910, '2026-11-09 10:20:00');
    makeCluster(52.400, 4.920, '2026-11-09 10:30:00', count: 5, intervalMinutes: 3);

    app(LocationProcessorService::class)->processForDevice(
        'test-device',
        Carbon::parse('2026-11-09 09:00:00'),
        Carbon::parse('2026-11-09 12:00:00'),
    );

    $trip = LocationTrip::firstOrFail();
    expect($trip->polyline ?? [])->not->toBeEmpty();
});

it('links a visit to an existing place when within merge radius', function (): void {
    // Visit A at place X
    makeCluster(52.370, 4.890, '2026-11-09 10:00:00', count: 5, intervalMinutes: 3);
    // Trip away and back
    makePoint(52.390, 4.910, '2026-11-09 10:20:00', speed: 14.0, motion: ['driving']);
    makePoint(52.400, 4.920, '2026-11-09 10:25:00', speed: 13.0, motion: ['driving']);
    makePoint(52.390, 4.910, '2026-11-09 10:30:00', speed: 14.0, motion: ['driving']);
    makePoint(52.371, 4.891, '2026-11-09 10:35:00', speed: 5.0);
    // Visit B at the same place X (slight offset, still within 150 m merge radius)
    makeCluster(52.3701, 4.8901, '2026-11-09 10:40:00', count: 5, intervalMinutes: 3);

    app(LocationProcessorService::class)->processForDevice(
        'test-device',
        Carbon::parse('2026-11-09 09:00:00'),
        Carbon::parse('2026-11-09 13:00:00'),
    );

    expect(Place::count())->toBe(1)
        ->and(LocationVisit::count())->toBe(2);

    $placeId = Place::firstOrFail()->id;
    expect(LocationVisit::where('place_id', $placeId)->count())->toBe(2);
});

it('creates separate places for visits far apart', function (): void {
    makeCluster(52.370, 4.890, '2026-11-09 10:00:00', count: 5, intervalMinutes: 3);
    makeCluster(52.500, 5.100, '2026-11-09 11:00:00', count: 5, intervalMinutes: 3);

    app(LocationProcessorService::class)->processForDevice(
        'test-device',
        Carbon::parse('2026-11-09 09:00:00'),
        Carbon::parse('2026-11-09 13:00:00'),
    );

    expect(Place::count())->toBe(2);
});

it('filters out points with poor accuracy', function (): void {
    // One good cluster
    makeCluster(52.370, 4.890, '2026-11-09 10:00:00', count: 5, intervalMinutes: 3);

    // Extra points with accuracy > 100 m — should be excluded from processing
    makePoint(52.371, 4.891, '2026-11-09 10:20:00', accuracy: 200.0);
    makePoint(52.372, 4.892, '2026-11-09 10:25:00', accuracy: 500.0);

    app(LocationProcessorService::class)->processForDevice(
        'test-device',
        Carbon::parse('2026-11-09 09:00:00'),
        Carbon::parse('2026-11-09 11:00:00'),
    );

    // The inaccurate points should not break the visit or create extra trips
    expect(LocationVisit::count())->toBe(1)
        ->and(LocationTrip::count())->toBe(0);
});

it('is idempotent — reprocessing the same window gives the same result', function (): void {
    makeCluster(52.370, 4.890, '2026-11-09 10:00:00', count: 5, intervalMinutes: 3);
    makePoint(52.380, 4.900, '2026-11-09 10:20:00', speed: 12.0);
    makePoint(52.390, 4.910, '2026-11-09 10:25:00', speed: 11.0);
    makeCluster(52.400, 4.920, '2026-11-09 10:35:00', count: 5, intervalMinutes: 3);

    $processor = app(LocationProcessorService::class);
    $window = [Carbon::parse('2026-11-09 09:00:00'), Carbon::parse('2026-11-09 12:00:00')];

    $processor->processForDevice('test-device', ...$window);
    $processor->processForDevice('test-device', ...$window);
    $processor->processForDevice('test-device', ...$window);

    expect(LocationVisit::count())->toBe(2)
        ->and(LocationTrip::count())->toBe(1);
});

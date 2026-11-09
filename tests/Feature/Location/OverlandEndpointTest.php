<?php

declare(strict_types=1);

use App\Models\LocationPoint;

// Helper: minimal valid Overland payload
function overlandBatch(int $count = 1, string $deviceId = 'iphone', string $timestamp = '2026-11-09T14:51:00Z'): array
{
    $locations = [];

    for ($i = 0; $i < $count; $i++) {
        $t = (new DateTimeImmutable($timestamp))->modify("+{$i} minutes")->format(DateTime::ATOM);

        $locations[] = [
            'type' => 'Feature',
            'geometry' => ['type' => 'Point', 'coordinates' => [4.8952, 52.3702]],
            'properties' => [
                'timestamp' => $t,
                'device_id' => $deviceId,
                'altitude' => 2,
                'speed' => 13.4,
                'horizontal_accuracy' => 5,
                'motion' => ['driving'],
                'battery_level' => 0.8,
                'battery_state' => 'unplugged',
            ],
        ];
    }

    return ['locations' => $locations];
}

beforeEach(function (): void {
    config(['services.overland.token' => 'test-secret']);
});

it('stores a valid batch and returns ok', function (): void {
    $this->postJson('/api/location/overland', overlandBatch(), [
        'Authorization' => 'Bearer test-secret',
    ])
        ->assertOk()
        ->assertJson(['result' => 'ok']);

    expect(LocationPoint::count())->toBe(1);
});

it('stores all points from a multi-point batch', function (): void {
    $this->postJson('/api/location/overland', overlandBatch(5), [
        'Authorization' => 'Bearer test-secret',
    ])->assertOk();

    expect(LocationPoint::count())->toBe(5);
});

it('accepts the token via access_token query param', function (): void {
    $this->postJson('/api/location/overland?access_token=test-secret', overlandBatch())
        ->assertOk()
        ->assertJson(['result' => 'ok']);
});

it('returns 401 with an invalid token', function (): void {
    $this->postJson('/api/location/overland', overlandBatch(), [
        'Authorization' => 'Bearer wrong-token',
    ])->assertUnauthorized();

    expect(LocationPoint::count())->toBe(0);
});

it('returns 401 when no token is provided', function (): void {
    $this->postJson('/api/location/overland', overlandBatch())
        ->assertUnauthorized();
});

it('does not create duplicates when the same batch is sent twice', function (): void {
    $batch = overlandBatch(3);

    $this->postJson('/api/location/overland', $batch, ['Authorization' => 'Bearer test-secret'])
        ->assertOk();

    $this->postJson('/api/location/overland', $batch, ['Authorization' => 'Bearer test-secret'])
        ->assertOk();

    expect(LocationPoint::count())->toBe(3);
});

it('stores a point with only required fields present', function (): void {
    $batch = [
        'locations' => [[
            'type' => 'Feature',
            'geometry' => ['type' => 'Point', 'coordinates' => [4.8952, 52.3702]],
            'properties' => ['timestamp' => '2026-11-09T15:00:00Z'],
        ]],
    ];

    $this->postJson('/api/location/overland', $batch, ['Authorization' => 'Bearer test-secret'])
        ->assertOk()
        ->assertJson(['result' => 'ok']);

    $point = LocationPoint::first();
    expect($point->altitude)->toBeNull()
        ->and($point->speed)->toBeNull()
        ->and($point->motion)->toBeNull()
        ->and($point->battery_level)->toBeNull();
});

it('correctly maps GeoJSON lon/lat order to database columns', function (): void {
    $this->postJson('/api/location/overland', overlandBatch(), ['Authorization' => 'Bearer test-secret'])
        ->assertOk();

    $point = LocationPoint::first();
    expect($point->longitude)->toBe(4.8952)
        ->and($point->latitude)->toBe(52.3702);
});

it('returns 422 when locations array is missing', function (): void {
    $this->postJson('/api/location/overland', [], ['Authorization' => 'Bearer test-secret'])
        ->assertUnprocessable();
});

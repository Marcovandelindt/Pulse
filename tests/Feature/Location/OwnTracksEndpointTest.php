<?php

declare(strict_types=1);

use App\Models\LocationPoint;

function ownTracksPayload(string $timestamp = '2026-11-09T14:51:00Z', float $lat = 52.3702, float $lon = 4.8952): array
{
    return [
        '_type' => 'location',
        'lat'   => $lat,
        'lon'   => $lon,
        'alt'   => 2,
        'acc'   => 5,
        'vel'   => 48,
        'batt'  => 80,
        'tst'   => (new DateTimeImmutable($timestamp))->getTimestamp(),
        'tid'   => 'MA',
        't'     => 'u',
    ];
}

beforeEach(function (): void {
    config(['services.owntracks.user' => 'marco', 'services.owntracks.password' => 'test-secret']);
});

it('stores a valid location ping and returns an empty array', function (): void {
    $this->withBasicAuth('marco', 'test-secret')
        ->postJson('/api/location/owntracks', ownTracksPayload())
        ->assertOk()
        ->assertExactJson([]);

    expect(LocationPoint::count())->toBe(1);
});

it('returns 401 with wrong credentials', function (): void {
    $this->withBasicAuth('marco', 'wrong-password')
        ->postJson('/api/location/owntracks', ownTracksPayload())
        ->assertUnauthorized();

    expect(LocationPoint::count())->toBe(0);
});

it('returns 401 when no credentials are provided', function (): void {
    $this->postJson('/api/location/owntracks', ownTracksPayload())
        ->assertUnauthorized();
});

it('returns 401 when credentials are not configured', function (): void {
    config(['services.owntracks.user' => null, 'services.owntracks.password' => null]);

    $this->withBasicAuth('marco', 'test-secret')
        ->postJson('/api/location/owntracks', ownTracksPayload())
        ->assertUnauthorized();
});

it('does not create a duplicate when the same point is sent twice', function (): void {
    $payload = ownTracksPayload();

    $this->withBasicAuth('marco', 'test-secret')->postJson('/api/location/owntracks', $payload)->assertOk();
    $this->withBasicAuth('marco', 'test-secret')->postJson('/api/location/owntracks', $payload)->assertOk();

    expect(LocationPoint::count())->toBe(1);
});

it('stores a point with only required fields present', function (): void {
    $payload = [
        '_type' => 'location',
        'lat'   => 52.3702,
        'lon'   => 4.8952,
        'tst'   => (new DateTimeImmutable('2026-11-09T15:00:00Z'))->getTimestamp(),
    ];

    $this->withBasicAuth('marco', 'test-secret')
        ->postJson('/api/location/owntracks', $payload)
        ->assertOk();

    $point = LocationPoint::first();
    expect($point->altitude)->toBeNull()
        ->and($point->speed)->toBeNull()
        ->and($point->motion)->toBeNull()
        ->and($point->battery_level)->toBeNull();
});

it('correctly stores lat and lon', function (): void {
    $this->withBasicAuth('marco', 'test-secret')
        ->postJson('/api/location/owntracks', ownTracksPayload())
        ->assertOk();

    $point = LocationPoint::first();
    expect($point->latitude)->toBe(52.3702)
        ->and($point->longitude)->toBe(4.8952);
});

it('uses the basic auth username as device_id', function (): void {
    $this->withBasicAuth('marco', 'test-secret')
        ->postJson('/api/location/owntracks', ownTracksPayload())
        ->assertOk();

    expect(LocationPoint::first()->device_id)->toBe('marco');
});

it('converts batt percentage to 0–1 battery level', function (): void {
    $this->withBasicAuth('marco', 'test-secret')
        ->postJson('/api/location/owntracks', ownTracksPayload())
        ->assertOk();

    expect(LocationPoint::first()->battery_level)->toBe(0.8);
});

it('silently ignores non-location event types', function (): void {
    $this->withBasicAuth('marco', 'test-secret')
        ->postJson('/api/location/owntracks', ['_type' => 'transition', 'event' => 'enter'])
        ->assertOk()
        ->assertExactJson([]);

    expect(LocationPoint::count())->toBe(0);
});

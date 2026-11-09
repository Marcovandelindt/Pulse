<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $device_id
 * @property Carbon $started_at
 * @property Carbon $ended_at
 * @property float $distance_meters
 * @property string $dominant_motion
 * @property int $point_count
 * @property list<array{float, float}> $polyline
 */
final class LocationTrip extends Model
{
    protected $fillable = [
        'device_id',
        'started_at',
        'ended_at',
        'distance_meters',
        'dominant_motion',
        'point_count',
        'polyline',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'distance_meters' => 'float',
            'point_count' => 'integer',
            'polyline' => 'array',
        ];
    }
}

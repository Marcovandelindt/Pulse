<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

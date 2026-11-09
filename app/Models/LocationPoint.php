<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class LocationPoint extends Model
{
    // Points are immutable; created_at is set by DB default, no updated_at
    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'recorded_at',
        'latitude',
        'longitude',
        'altitude',
        'speed',
        'horizontal_accuracy',
        'motion',
        'battery_level',
        'raw',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
            'altitude' => 'float',
            'speed' => 'float',
            'horizontal_accuracy' => 'float',
            'battery_level' => 'float',
            'motion' => 'array',
            'raw' => 'array',
            'created_at' => 'datetime',
        ];
    }
}

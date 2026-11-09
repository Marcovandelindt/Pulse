<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LocationVisit extends Model
{
    protected $fillable = [
        'place_id',
        'device_id',
        'started_at',
        'ended_at',
        'centroid_latitude',
        'centroid_longitude',
        'point_count',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'centroid_latitude' => 'float',
            'centroid_longitude' => 'float',
            'point_count' => 'integer',
        ];
    }

    /** @return BelongsTo<Place, $this> */
    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}

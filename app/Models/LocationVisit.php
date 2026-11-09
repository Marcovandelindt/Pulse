<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $place_id
 * @property string $device_id
 * @property Carbon $started_at
 * @property Carbon $ended_at
 * @property float $centroid_latitude
 * @property float $centroid_longitude
 * @property int $point_count
 */
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

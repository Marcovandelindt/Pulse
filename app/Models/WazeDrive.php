<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\WazeDriveFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $driven_at
 * @property string|null $origin_name
 * @property float|null $origin_lat
 * @property float|null $origin_lon
 * @property string|null $destination_name
 * @property float|null $destination_lat
 * @property float|null $destination_lon
 * @property float|null $distance_km
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class WazeDrive extends Model
{
    /** @use HasFactory<WazeDriveFactory> */
    use HasFactory;

    protected $fillable = [
        'driven_at',
        'origin_name',
        'origin_lat',
        'origin_lon',
        'destination_name',
        'destination_lat',
        'destination_lon',
        'distance_km',
    ];

    protected function casts(): array
    {
        return [
            'driven_at' => 'datetime',
            'origin_lat' => 'float',
            'origin_lon' => 'float',
            'destination_lat' => 'float',
            'destination_lon' => 'float',
            'distance_km' => 'float',
        ];
    }
}

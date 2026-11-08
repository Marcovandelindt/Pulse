<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property float $lat
 * @property float $lon
 */
final class WazeWaypoint extends Model
{
    public $timestamps = false;

    protected $fillable = ['lat', 'lon'];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lon' => 'float',
        ];
    }
}

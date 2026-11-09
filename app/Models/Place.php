<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Place extends Model
{
    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'visit_count',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'visit_count' => 'integer',
        ];
    }

    /** @return HasMany<LocationVisit, $this> */
    public function visits(): HasMany
    {
        return $this->hasMany(LocationVisit::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CodMap extends Model
{
    protected $fillable = [
        'name',
    ];

    /** @return HasMany<CodMatch, $this> */
    public function matches(): HasMany
    {
        return $this->hasMany(CodMatch::class);
    }
}

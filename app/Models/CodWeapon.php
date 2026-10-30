<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CodWeapon extends Model
{
    protected $fillable = [
        'name',
        'category',
    ];

    /** @return HasMany<CodMatch, $this> */
    public function matches(): HasMany
    {
        return $this->hasMany(CodMatch::class, 'top_weapon_id');
    }
}

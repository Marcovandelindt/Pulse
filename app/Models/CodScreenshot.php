<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class CodScreenshot extends Model
{
    protected $fillable = [
        'filename',
        'file_hash',
        'taken_at',
    ];

    protected function casts(): array
    {
        return [
            'taken_at' => 'datetime',
        ];
    }

    /** @return BelongsToMany<CodMatch, $this> */
    public function matches(): BelongsToMany
    {
        return $this->belongsToMany(CodMatch::class);
    }
}

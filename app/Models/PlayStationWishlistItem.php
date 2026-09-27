<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlayStationWishlistItem extends Model
{
    /** @use HasFactory<\Database\Factories\PlayStationWishlistItemFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'platform',
        'price',
        'psn_url',
        'notes',
        'purchased_at',
    ];

    protected function casts(): array
    {
        return [
            'price'        => 'decimal:2',
            'purchased_at' => 'datetime',
        ];
    }

    public function isPurchased(): bool
    {
        return $this->purchased_at !== null;
    }
}

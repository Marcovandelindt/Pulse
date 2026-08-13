<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Album extends Model
{
    protected $fillable = ['name', 'spotify_album_id', 'image_url', 'release_date', 'album_type', 'total_tracks'];

    protected function casts(): array
    {
        return [
            'release_date' => 'date',
        ];
    }

    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class);
    }

    protected function releaseYear(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->release_date?->year,
        );
    }
}

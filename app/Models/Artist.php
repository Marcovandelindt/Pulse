<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Artist extends Model
{
    protected $fillable = ['name', 'spotify_artist_id', 'image_url', 'genres', 'popularity'];

    protected function casts(): array
    {
        return [
            'genres' => 'array',
        ];
    }

    public function tracks(): BelongsToMany
    {
        return $this->belongsToMany(Track::class, 'track_artists')
            ->withPivot('is_primary', 'sort_order')
            ->withTimestamps();
    }
}

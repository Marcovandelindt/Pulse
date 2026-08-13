<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Artist extends Model
{
    protected $fillable = [
        'spotify_id',
        'name',
        'image_path',
        'genres',
        'popularity',
    ];

    protected function casts(): array
    {
        return [
            'genres' => 'array',
            'popularity' => 'integer',
        ];
    }

    public function albums(): HasMany
    {
        return $this->hasMany(Album::class)->orderBy('name');
    }

    public function featuredAlbums(): BelongsToMany
    {
        return $this->belongsToMany(Album::class, 'album_artist')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->image_path);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Album extends Model
{
    protected $fillable = [
        'spotify_id',
        'artist_id',
        'name',
        'image_path',
        'release_date',
        'release_year',
        'track_count',
        'duration_ms',
        'genres',
        'album_type',
        'label',
        'listen_count',
        'last_listened_at',
        'first_listened_at',
    ];

    protected function casts(): array
    {
        return [
            'release_date' => 'date',
            'genres' => 'array',
            'last_listened_at' => 'datetime',
            'first_listened_at' => 'datetime',
        ];
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function artists(): BelongsToMany
    {
        return $this->belongsToMany(Artist::class, 'album_artist')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class)
            ->orderBy('disc_number')
            ->orderBy('track_number');
    }

    public function listens(): HasMany
    {
        return $this->hasMany(AlbumListen::class)
            ->orderByRaw('listened_at DESC NULLS LAST');
    }

    public function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->image_path);
    }

    public function formattedDuration(): Attribute
    {
        return Attribute::get(function () {
            if ($this->duration_ms === null) {
                return null;
            }

            $totalSeconds = (int) round($this->duration_ms / 1000);
            $hours = intdiv($totalSeconds, 3600);
            $minutes = intdiv($totalSeconds % 3600, 60);

            return $hours > 0
                ? "{$hours}u {$minutes}m"
                : "{$minutes}m";
        });
    }

    public function incrementListenCount(): void
    {
        $this->listen_count++;
        $this->last_listened_at = now();
        $this->first_listened_at ??= now();
        $this->save();
    }
}

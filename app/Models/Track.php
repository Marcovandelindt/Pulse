<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Track extends Model
{
    protected $fillable = [
        'title',
        'spotify_track_id',
        'album_id',
        'duration_ms',
        'popularity',
        'preview_url',
        'spotify_uri',
        'is_explicit',
        'is_obsession',
        'obsession_since',
    ];

    protected function casts(): array
    {
        return [
            'is_explicit' => 'boolean',
            'is_obsession' => 'boolean',
            'obsession_since' => 'datetime',
        ];
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    public function artists(): BelongsToMany
    {
        return $this->belongsToMany(Artist::class, 'track_artists')
            ->withPivot('is_primary', 'sort_order')
            ->withTimestamps()
            ->orderBy('track_artists.sort_order');
    }

    public function plays(): HasMany
    {
        return $this->hasMany(Play::class);
    }

    protected function primaryArtist(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->artists->firstWhere('pivot.is_primary', true) ?? $this->artists->first(),
        );
    }

    protected function artistsString(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->artists->pluck('name')->implode(', '),
        );
    }

    protected function formattedDuration(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->duration_ms === null) {
                    return '—';
                }

                $totalSeconds = (int) round($this->duration_ms / 1000);
                $minutes = intdiv($totalSeconds, 60);
                $seconds = $totalSeconds % 60;

                return sprintf('%d:%02d', $minutes, $seconds);
            },
        );
    }
}

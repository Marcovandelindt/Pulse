<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Track extends Model
{
    protected $fillable = [
        'spotify_id',
        'album_id',
        'name',
        'track_number',
        'disc_number',
        'duration_ms',
        'preview_url',
        'is_explicit',
    ];

    protected function casts(): array
    {
        return [
            'is_explicit' => 'boolean',
        ];
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(Album::class);
    }

    public function formattedDuration(): Attribute
    {
        return Attribute::get(function () {
            if ($this->duration_ms === null) {
                return null;
            }

            $totalSeconds = (int) round($this->duration_ms / 1000);
            $minutes = intdiv($totalSeconds, 60);
            $seconds = $totalSeconds % 60;

            return sprintf('%d:%02d', $minutes, $seconds);
        });
    }
}

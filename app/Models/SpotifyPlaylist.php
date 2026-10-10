<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class SpotifyPlaylist extends Model
{
    protected $fillable = [
        'spotify_playlist_id',
        'name',
        'period_type',
        'period_from',
        'period_to',
        'track_count',
        'url',
    ];

    protected function casts(): array
    {
        return [
            'period_from' => 'date',
            'period_to' => 'date',
        ];
    }

    public function tracks(): BelongsToMany
    {
        return $this->belongsToMany(Track::class, 'spotify_playlist_tracks')
            ->withPivot('play_count', 'position')
            ->orderBy('spotify_playlist_tracks.position');
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EpisodeWatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EpisodeWatch extends Model
{
    /** @use HasFactory<EpisodeWatchFactory> */
    use HasFactory;

    protected $fillable = ['tv_episode_id', 'watched_at', 'year_only', 'notes', 'rating'];

    protected function casts(): array
    {
        return [
            'watched_at' => 'datetime',
            'year_only' => 'boolean',
        ];
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(TvEpisode::class, 'tv_episode_id');
    }

    public function formattedWatchedAt(): string
    {
        if ($this->watched_at === null) {
            return 'Somewhere in your life';
        }

        return $this->year_only
            ? $this->watched_at->format('Y')
            : $this->watched_at->format('d M Y');
    }
}

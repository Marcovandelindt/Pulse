<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MovieWatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MovieWatch extends Model
{
    /** @use HasFactory<MovieWatchFactory> */
    use HasFactory;

    protected $fillable = ['movie_id', 'watched_at', 'year_only', 'notes', 'rating'];

    protected function casts(): array
    {
        return [
            'watched_at' => 'datetime',
            'year_only' => 'boolean',
        ];
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    public function formattedWatchedAt(): string
    {
        if ($this->watched_at === null) {
            return 'Date unknown';
        }

        return $this->year_only
            ? $this->watched_at->format('Y')
            : $this->watched_at->format('d M Y');
    }
}

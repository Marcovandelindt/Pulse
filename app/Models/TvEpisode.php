<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\TvEpisodeFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TvEpisode extends Model
{
    /** @use HasFactory<TvEpisodeFactory> */
    use HasFactory;

    protected $fillable = [
        'tv_season_id', 'tmdb_id', 'name', 'overview',
        'episode_number', 'air_date', 'runtime', 'vote_average',
    ];

    protected function casts(): array
    {
        return [
            'air_date' => 'date',
            'vote_average' => 'float',
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(TvSeason::class, 'tv_season_id');
    }

    public function watches(): HasMany
    {
        return $this->hasMany(EpisodeWatch::class);
    }

    public function isWatched(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->watches_count > 0 || $this->relationLoaded('watches') && $this->watches->isNotEmpty(),
        );
    }

    public function watchCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->watches_count ?? $this->watches->count(),
        );
    }

    public function addWatch(
        ?Carbon $watchedAt = null,
        bool $yearOnly = false,
        ?string $notes = null,
        ?int $rating = null,
    ): EpisodeWatch {
        $watch = $this->watches()->create([
            'watched_at' => $watchedAt,
            'year_only' => $yearOnly,
            'notes' => $notes,
            'rating' => $rating,
        ]);

        $season = $this->season()->with('series')->first();
        $season->updateProgress();
        $season->series->recordWatch();

        return $watch;
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TvSeriesFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TvSeries extends Model
{
    /** @use HasFactory<TvSeriesFactory> */
    use HasFactory;

    protected $table = 'tv_series';

    protected $fillable = [
        'tmdb_id', 'name', 'name_en', 'original_name', 'overview',
        'poster_path', 'backdrop_path', 'custom_backdrop_url', 'first_air_date', 'last_air_date',
        'vote_average', 'user_rating', 'genres', 'status', 'original_language',
        'number_of_seasons', 'number_of_episodes', 'episodes_watched',
        'watched_runtime_minutes', 'completion_percentage', 'last_watched_at', 'first_watched_at',
        'is_favorite',
        'exclude_cast',
    ];

    protected function casts(): array
    {
        return [
            'first_air_date' => 'date',
            'last_air_date' => 'date',
            'genres' => 'array',
            'last_watched_at' => 'datetime',
            'first_watched_at' => 'datetime',
            'is_favorite'   => 'boolean',
            'exclude_cast'  => 'boolean',
        ];
    }

    public function seasons(): HasMany
    {
        return $this->hasMany(TvSeason::class)->orderBy('season_number');
    }

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'tv_series_person')
            ->withPivot('character', 'department', 'job', 'cast_order', 'episode_count', 'excluded')
            ->withTimestamps();
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('completion_percentage', '>=', 100);
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('completion_percentage', '>', 0)
            ->where('completion_percentage', '<', 100);
    }

    public function posterUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->poster_path
                ? config('tmdb.image_base_url').config('tmdb.poster_sizes.medium').$this->poster_path
                : null,
        );
    }

    public function backdropUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->backdrop_path
                ? config('tmdb.image_base_url').config('tmdb.backdrop_sizes.large').$this->backdrop_path
                : null,
        );
    }

    public function updateProgress(): void
    {
        $this->episodes_watched = $this->seasons->sum('episodes_watched');
        $this->completion_percentage = $this->number_of_episodes > 0
            ? round(($this->episodes_watched / $this->number_of_episodes) * 100, 2)
            : 0;
        $this->watched_runtime_minutes = EpisodeWatch::join('tv_episodes', 'episode_watches.tv_episode_id', '=', 'tv_episodes.id')
            ->whereIn('tv_episodes.tv_season_id', $this->seasons()->select('id'))
            ->whereNotNull('tv_episodes.runtime')
            ->sum('tv_episodes.runtime');
        $this->save();
    }

    public function recordWatch(): void
    {
        $seasonIds = $this->seasons()->select('id');

        $latest = EpisodeWatch::join('tv_episodes', 'episode_watches.tv_episode_id', '=', 'tv_episodes.id')
            ->whereIn('tv_episodes.tv_season_id', $seasonIds)
            ->whereNotNull('episode_watches.watched_at')
            ->max('episode_watches.watched_at');

        $earliest = EpisodeWatch::join('tv_episodes', 'episode_watches.tv_episode_id', '=', 'tv_episodes.id')
            ->whereIn('tv_episodes.tv_season_id', $seasonIds)
            ->whereNotNull('episode_watches.watched_at')
            ->min('episode_watches.watched_at');

        $this->last_watched_at  = $latest   ? \Carbon\Carbon::parse($latest)   : now();
        $this->first_watched_at ??= $earliest ? \Carbon\Carbon::parse($earliest) : now();
        $this->updateProgress();
    }
}

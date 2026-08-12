<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TvSeasonFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TvSeason extends Model
{
    /** @use HasFactory<TvSeasonFactory> */
    use HasFactory;

    protected $fillable = [
        'tv_series_id', 'tmdb_id', 'name', 'overview',
        'poster_path', 'season_number', 'air_date',
        'episode_count', 'episodes_watched',
    ];

    protected function casts(): array
    {
        return [
            'air_date' => 'date',
        ];
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(TvSeries::class, 'tv_series_id');
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(TvEpisode::class)->orderBy('episode_number');
    }

    public function posterUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->poster_path
                ? config('tmdb.image_base_url').config('tmdb.poster_sizes.medium').$this->poster_path
                : null,
        );
    }

    public function updateProgress(): void
    {
        $this->episodes_watched = $this->episodes()
            ->whereHas('watches')
            ->count();
        $this->save();
    }
}

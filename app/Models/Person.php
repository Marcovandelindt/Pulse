<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory;

    protected $fillable = ['tmdb_id', 'name', 'profile_path'];

    public function movies(): BelongsToMany
    {
        return $this->belongsToMany(Movie::class)
            ->withPivot('character', 'department', 'job', 'cast_order')
            ->withTimestamps();
    }

    public function tvSeries(): BelongsToMany
    {
        return $this->belongsToMany(TvSeries::class, 'tv_series_person')
            ->withPivot('character', 'department', 'job', 'cast_order', 'episode_count')
            ->withTimestamps();
    }

    public function profileUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->profile_path
                ? config('tmdb.image_base_url').config('tmdb.profile_sizes.medium').$this->profile_path
                : null,
        );
    }
}

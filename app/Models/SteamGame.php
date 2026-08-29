<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BacklogStatus;
use App\Enums\PlayMode;
use App\Traits\HasBacklogStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SteamGame extends Model
{
    use HasBacklogStatus;

    protected $fillable = [
        'steam_account_id',
        'steam_appid',
        'name',
        'image_url',
        'custom_image_url',
        'playtime_minutes',
        'playtime_2weeks_minutes',
        'last_played_at',
        'price',
        'backlog_status',
        'play_mode',
        'main_story_completed',
        'user_rating',
        'critic_rating',
    ];

    protected function casts(): array
    {
        return [
            'last_played_at'       => 'datetime',
            'main_story_completed' => 'boolean',
            'backlog_status'       => BacklogStatus::class,
            'play_mode'            => PlayMode::class,
            'price'                => 'decimal:2',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(SteamAccount::class, 'steam_account_id');
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'genre_steam_game');
    }

    public function tracks(): MorphMany
    {
        return $this->morphMany(Track::class, 'gameable');
    }

    public function scopeMostPlayed(Builder $query, int $limit = 10): Builder
    {
        return $query->orderByDesc('playtime_minutes')->limit($limit);
    }

    public function scopeRecentlyPlayed(Builder $query, int $limit = 10): Builder
    {
        return $query->orderByDesc('last_played_at')->limit($limit);
    }

    public function scopePlayedRecently(Builder $query): Builder
    {
        return $query->where('playtime_2weeks_minutes', '>', 0);
    }

    public function scopeNeverPlayed(Builder $query): Builder
    {
        return $query->where('playtime_minutes', 0);
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->custom_image_url ?? $this->attributes['image_url'] ?? null,
        );
    }

    protected function playtimeHours(): Attribute
    {
        return Attribute::make(
            get: fn () => round($this->playtime_minutes / 60, 1),
        );
    }

    protected function formattedPlaytime(): Attribute
    {
        return Attribute::make(
            get: function () {
                $hours = intdiv($this->playtime_minutes, 60);
                $minutes = $this->playtime_minutes % 60;

                if ($hours === 0) {
                    return "{$minutes}m";
                }

                return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
            },
        );
    }

    protected function playtime2weeksHours(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->playtime_2weeks_minutes !== null
                ? round($this->playtime_2weeks_minutes / 60, 1)
                : null,
        );
    }

    protected function steamUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => "https://store.steampowered.com/app/{$this->steam_appid}",
        );
    }
}

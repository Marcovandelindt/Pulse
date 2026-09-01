<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BacklogStatus;
use App\Enums\PlayMode;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

final class PlayStationGame extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'platform',
        'image_url',
        'hours',
        'sessions',
        'avg_session_minutes',
        'last_played_at',
        'trophies',
        'completion_percentage',
        'psn_url',
        'np_communication_id',
        'np_service_name',
        'price',
        'psn_total_minutes',
        'exclude_from_sync',
        'backlog_status',
        'user_rating',
        'critic_rating',
        'play_mode',
        'main_story_completed',
        'is_favorite',
        'trophy_progress',
        'trophy_earned',
        'trophy_defined',
        'trophies_last_synced_at',
        'completed_at',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'backlog_status' => BacklogStatus::class,
            'play_mode' => AsEnumCollection::of(PlayMode::class),
            'last_played_at' => 'date',
            'released_at' => 'date',
            'main_story_completed' => 'boolean',
            'exclude_from_sync' => 'boolean',
            'is_favorite' => 'boolean',
            'hours' => 'decimal:2',
            'completion_percentage' => 'decimal:2',
            'trophy_earned' => 'array',
            'trophy_defined' => 'array',
            'trophies_last_synced_at' => 'datetime',
            'completed_at'            => 'datetime',
            'price' => 'decimal:2',
            'user_rating' => 'decimal:1',
            'critic_rating' => 'decimal:1',
        ];
    }

    public function playSessions(): HasMany
    {
        return $this->hasMany(PlayStationSession::class);
    }

    public function trophyList(): HasMany
    {
        return $this->hasMany(PlayStationTrophy::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(PlayStationCategory::class, 'play_station_game_category');
    }

    public function tracks(): MorphMany
    {
        return $this->morphMany(Track::class, 'gameable');
    }

    protected function label(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->display_name ?? $this->name
        );
    }

    protected function calculatedHours(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->psn_total_minutes) {
                    return round($this->psn_total_minutes / 60, 1);
                }

                return round($this->trackedHours, 1);
            }
        );
    }

    protected function trackedHours(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->relationLoaded('playSessions')) {
                    $sessions = $this->released_at
                        ? $this->playSessions->filter(fn ($s) => $s->started_at >= $this->released_at)
                        : $this->playSessions;

                    return $sessions->sum('duration_minutes') / 60;
                }

                return (float) ($this->getAttributes()['filtered_minutes'] ?? 0) / 60;
            }
        );
    }

    protected function formattedHours(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format((float) $this->hours, 1).'h'
        );
    }

    protected function calculatedSessions(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->released_at
                    ? $this->playSessions->filter(fn ($s) => $s->started_at >= $this->released_at)->count()
                    : $this->playSessions->count();
            }
        );
    }

    protected function formattedAvgSession(): Attribute
    {
        return Attribute::make(
            get: function () {
                $sessions = $this->released_at
                    ? $this->playSessions->filter(fn ($s) => $s->started_at >= $this->released_at)
                    : $this->playSessions;

                $count = $sessions->count();

                if ($count === 0) {
                    return '—';
                }

                $avgMinutes = (int) round($sessions->avg('duration_minutes'));

                if ($avgMinutes >= 60) {
                    $hours = intdiv($avgMinutes, 60);
                    $minutes = $avgMinutes % 60;

                    return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
                }

                return "{$avgMinutes}m";
            }
        );
    }

    public function platformColor(): string
    {
        return match ($this->platform) {
            'PS5' => '#003087',
            'PS4' => '#00439c',
            'PS3' => '#003791',
            'PSVITA' => '#003087',
            default => '#003087',
        };
    }
}

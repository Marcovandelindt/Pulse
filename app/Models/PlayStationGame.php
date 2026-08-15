<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BacklogStatus;
use App\Enums\PlayMode;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PlayStationGame extends Model
{
    protected $fillable = [
        'name',
        'platform',
        'image_url',
        'hours',
        'sessions',
        'avg_session_minutes',
        'last_played_at',
        'trophies',
        'completion_percentage',
        'psn_url',
        'price',
        'manual_minutes',
        'exclude_from_sync',
        'backlog_status',
        'user_rating',
        'critic_rating',
        'play_mode',
        'main_story_completed',
    ];

    protected function casts(): array
    {
        return [
            'backlog_status' => BacklogStatus::class,
            'play_mode' => AsEnumCollection::of(PlayMode::class),
            'last_played_at' => 'date',
            'main_story_completed' => 'boolean',
            'exclude_from_sync' => 'boolean',
            'hours' => 'decimal:2',
            'completion_percentage' => 'decimal:2',
            'price' => 'decimal:2',
            'user_rating' => 'decimal:1',
            'critic_rating' => 'decimal:1',
        ];
    }

    public function playSessions(): HasMany
    {
        return $this->hasMany(PlayStationSession::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(PlayStationCategory::class, 'play_station_game_category');
    }

    protected function calculatedHours(): Attribute
    {
        return Attribute::make(
            get: function () {
                $fromSessions = $this->playSessions->sum('duration_minutes') / 60;
                $fromManual = ($this->manual_minutes ?? 0) / 60;

                return round($fromSessions + $fromManual, 1);
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
            get: fn () => $this->playSessions->count()
        );
    }

    protected function formattedAvgSession(): Attribute
    {
        return Attribute::make(
            get: function () {
                $count = $this->playSessions->count();

                if ($count === 0) {
                    return '—';
                }

                $avgMinutes = (int) round($this->playSessions->avg('duration_minutes'));

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

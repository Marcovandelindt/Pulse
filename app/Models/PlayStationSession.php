<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PlayStationSession extends Model
{
    protected $fillable = [
        'play_station_game_id',
        'started_at',
        'ended_at',
        'duration_minutes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(PlayStationGame::class, 'play_station_game_id');
    }

    public function scopeRecent(Builder $query, int $limit = 10): Builder
    {
        return $query->latest('started_at')->limit($limit);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('started_at', today());
    }

    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('started_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    protected function endTime(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->started_at->copy()->addMinutes($this->duration_minutes)
        );
    }

    protected function formattedDuration(): Attribute
    {
        return Attribute::make(
            get: function () {
                $minutes = $this->duration_minutes;

                if ($minutes >= 60) {
                    $hours = intdiv($minutes, 60);
                    $remaining = $minutes % 60;

                    return $remaining > 0 ? "{$hours}h {$remaining}m" : "{$hours}h";
                }

                return "{$minutes}m";
            }
        );
    }
}

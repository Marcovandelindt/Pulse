<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayStationTrophy extends Model
{
    protected $fillable = [
        'play_station_game_id',
        'trophy_id',
        'trophy_group_id',
        'name',
        'detail',
        'icon_url',
        'type',
        'is_earned',
        'earned_at',
    ];

    protected function casts(): array
    {
        return [
            'is_earned' => 'boolean',
            'earned_at' => 'datetime',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(PlayStationGame::class, 'play_station_game_id');
    }

    public function typeColor(): string
    {
        return match ($this->type) {
            'platinum' => '#6b7fe3',
            'gold'     => '#c9a227',
            'silver'   => '#9ea3a8',
            'bronze'   => '#b36a2a',
            default    => '#6b7280',
        };
    }
}

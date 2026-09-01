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
        'rarity',
        'earned_rate',
    ];

    protected function casts(): array
    {
        return [
            'is_earned'   => 'boolean',
            'earned_at'   => 'datetime',
            'earned_rate' => 'decimal:2',
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

    public function rarityLabel(): ?string
    {
        return match ($this->rarity) {
            0 => 'Ultra Rare',
            1 => 'Very Rare',
            2 => 'Rare',
            3 => 'Uncommon',
            4 => 'Common',
            default => null,
        };
    }

    public function rarityColor(): string
    {
        return match ($this->rarity) {
            0 => '#e2b842',
            1 => '#a78bfa',
            2 => '#60a5fa',
            3 => '#94a3b8',
            4 => '#64748b',
            default => '#64748b',
        };
    }
}

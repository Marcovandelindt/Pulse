<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamingPresence extends Model
{
    protected $fillable = [
        'platform',
        'game_id',
        'game_name',
        'image_url',
        'started_at',
        'last_seen_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at'   => 'datetime',
            'last_seen_at' => 'datetime',
            'ended_at'     => 'datetime',
        ];
    }

    /** @param Builder<GamingPresence> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('ended_at');
    }

    public function playStationGame(): BelongsTo
    {
        return $this->belongsTo(PlayStationGame::class, 'game_id');
    }
}

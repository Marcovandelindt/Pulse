<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PlayStationGameImage extends Model
{
    protected $fillable = [
        'play_station_game_id',
        'path',
        'caption',
        'sort_order',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(PlayStationGame::class, 'play_station_game_id');
    }
}

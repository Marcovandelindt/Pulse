<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class PlayStationCategory extends Model
{
    protected $fillable = ['name'];

    public function games(): BelongsToMany
    {
        return $this->belongsToMany(PlayStationGame::class, 'play_station_game_category');
    }
}

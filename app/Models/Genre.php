<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Genre extends Model
{
    protected $fillable = ['name'];

    public function steamGames(): BelongsToMany
    {
        return $this->belongsToMany(SteamGame::class, 'genre_steam_game');
    }
}

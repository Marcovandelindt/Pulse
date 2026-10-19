<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

final class SteamGameImage extends Model
{
    protected $fillable = [
        'steam_game_id',
        'path',
        'disk',
        'caption',
        'sort_order',
    ];

    protected function url(): Attribute
    {
        return Attribute::get(function () {
            if (($this->disk ?? 'public') === 'public') {
                return url('/storage/'.$this->path);
            }

            return Storage::disk('r2')->url($this->path);
        });
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(SteamGame::class, 'steam_game_id');
    }
}

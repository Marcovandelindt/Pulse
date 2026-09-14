<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NintendoDailyRecord extends Model
{
    protected $fillable = [
        'nintendo_game_id',
        'date',
        'minutes_played',
    ];

    protected function casts(): array
    {
        return [
            'date'           => 'date',
            'minutes_played' => 'integer',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(NintendoGame::class, 'nintendo_game_id');
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $play_station_game_id
 * @property string $title
 * @property string|null $body
 * @property Carbon|null $completed_at
 * @property int $order
 */
final class PlayStationGameTodo extends Model
{
    protected $fillable = ['play_station_game_id', 'title', 'body', 'completed_at', 'order'];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(PlayStationGame::class, 'play_station_game_id');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}

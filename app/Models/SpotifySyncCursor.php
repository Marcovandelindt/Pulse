<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

final class SpotifySyncCursor extends Model
{
    protected $fillable = ['last_played_at', 'synced_at', 'plays_imported'];

    protected function casts(): array
    {
        return [
            'last_played_at' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    public static function lastPlayedAt(): ?Carbon
    {
        return self::latest()->value('last_played_at');
    }

    public static function record(Carbon $playedAt, int $playsImported): self
    {
        return self::updateOrCreate([], [
            'last_played_at' => $playedAt,
            'synced_at' => now(),
            'plays_imported' => $playsImported,
        ]);
    }
}

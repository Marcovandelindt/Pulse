<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NintendoGame extends Model
{
    protected $fillable = [
        'application_id',
        'igdb_id',
        'name',
        'image_url',
        'genres',
        'released_at',
        'total_minutes',
        'first_played_at',
        'last_played_at',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'genres'          => 'array',
            'released_at'     => 'date',
            'first_played_at' => 'date',
            'last_played_at'  => 'date',
            'last_synced_at'  => 'datetime',
            'total_minutes'   => 'integer',
        ];
    }

    public function dailyRecords(): HasMany
    {
        return $this->hasMany(NintendoDailyRecord::class);
    }

    public function getFormattedHoursAttribute(): string
    {
        $hours   = intdiv($this->total_minutes, 60);
        $minutes = $this->total_minutes % 60;

        if ($hours === 0) {
            return "{$minutes}m";
        }

        return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
    }
}

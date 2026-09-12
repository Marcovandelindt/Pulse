<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthSleep extends Model
{
    protected $table = 'health_sleep';

    protected $fillable = [
        'date',
        'sleep_start',
        'sleep_end',
        'in_bed_start',
        'in_bed_end',
        'total_sleep_minutes',
        'awake_minutes',
        'rem_minutes',
        'deep_minutes',
        'core_minutes',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'date'         => 'date',
            'sleep_start'  => 'datetime',
            'sleep_end'    => 'datetime',
            'in_bed_start' => 'datetime',
            'in_bed_end'   => 'datetime',
        ];
    }

    public function formattedMinutes(?int $minutes): string
    {
        if ($minutes === null) {
            return '—';
        }

        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return $h > 0 ? "{$h}h {$m}m" : "{$m}m";
    }

    public function phasePercent(string $column, int $base): int
    {
        if ($base === 0 || $this->{$column} === null) {
            return 0;
        }

        return (int) round($this->{$column} / $base * 100);
    }
}

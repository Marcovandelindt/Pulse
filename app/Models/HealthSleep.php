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

    public function sleepScore(): int
    {
        $mins = $this->total_sleep_minutes ?? 0;

        $score = match(true) {
            $mins >= 480 && $mins <= 540 => 70,
            $mins >= 420 && $mins < 480  => 60,
            $mins > 540 && $mins <= 600  => 60,
            $mins >= 360 && $mins < 420  => 45,
            $mins > 600                  => 50,
            $mins >= 300 && $mins < 360  => 25,
            default                      => 10,
        };

        $base = ($this->deep_minutes ?? 0) + ($this->rem_minutes ?? 0)
              + ($this->core_minutes ?? 0) + ($this->awake_minutes ?? 0);

        if ($base > 0 && $this->deep_minutes !== null && $this->rem_minutes !== null) {
            $deepPct  = ($this->deep_minutes / $base) * 100;
            $remPct   = ($this->rem_minutes / $base) * 100;
            $awakePct = $this->awake_minutes ? ($this->awake_minutes / $base) * 100 : 0;

            if ($deepPct >= 20) $score += 15;
            elseif ($deepPct >= 15) $score += 8;
            elseif ($deepPct < 10) $score -= 5;

            if ($remPct >= 20) $score += 15;
            elseif ($remPct >= 15) $score += 8;
            elseif ($remPct < 10) $score -= 5;

            if ($awakePct > 15) $score -= 10;
            elseif ($awakePct > 10) $score -= 5;
        }

        return min(100, max(0, $score));
    }

    public function sleepScoreLabel(): string
    {
        $score = $this->sleepScore();

        return match(true) {
            $score >= 85 => 'Excellent',
            $score >= 70 => 'Good',
            $score >= 50 => 'Fair',
            default      => 'Poor',
        };
    }
}

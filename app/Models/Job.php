<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property list<string> $work_days
 * @property string|null $work_start_time  HH:MM
 * @property string|null $work_end_time    HH:MM
 */
class Job extends Model
{
    protected $table = 'work_jobs';

    protected $fillable = [
        'employer',
        'title',
        'started_at',
        'ended_at',
        'work_days',
        'work_start_time',
        'work_end_time',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'ended_at' => 'date',
            'work_days' => 'array',
        ];
    }

    public function isActive(): bool
    {
        return $this->ended_at === null;
    }

    public function wasWorkDay(Carbon $date): bool
    {
        if ($date->lt($this->started_at)) {
            return false;
        }

        if ($this->ended_at !== null && $date->gt($this->ended_at)) {
            return false;
        }

        return in_array(strtolower($date->englishDayOfWeek), $this->work_days, strict: true);
    }

    public function wasWorkTime(Carbon $datetime): bool
    {
        if (! $this->wasWorkDay($datetime->copy()->startOfDay())) {
            return false;
        }

        if ($this->work_start_time === null || $this->work_end_time === null) {
            return true;
        }

        $time = $datetime->format('H:i');

        return $time >= $this->work_start_time && $time <= $this->work_end_time;
    }

    /** @return array<string> */
    public static function allDays(): array
    {
        return ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    }

    public function formattedWorkDays(): string
    {
        $abbr = [
            'monday' => 'Mon',
            'tuesday' => 'Tue',
            'wednesday' => 'Wed',
            'thursday' => 'Thu',
            'friday' => 'Fri',
            'saturday' => 'Sat',
            'sunday' => 'Sun',
        ];

        return implode(', ', array_map(
            fn (string $day) => $abbr[$day] ?? ucfirst($day),
            $this->work_days,
        ));
    }

    public function durationLabel(): string
    {
        $end = $this->ended_at ?? now();
        $months = (int) $this->started_at->diffInMonths($end);

        if ($months < 1) {
            return 'Less than a month';
        }

        $years = intdiv($months, 12);
        $remain = $months % 12;

        $parts = [];

        if ($years > 0) {
            $parts[] = $years.' '.($years === 1 ? 'year' : 'years');
        }

        if ($remain > 0) {
            $parts[] = $remain.' '.($remain === 1 ? 'month' : 'months');
        }

        return implode(', ', $parts);
    }
}

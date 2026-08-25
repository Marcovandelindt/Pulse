<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class WorkSchedule extends Model
{
    protected $fillable = [
        'name',
        'days',
        'start_time',
        'end_time',
        'color',
        'valid_from',
        'valid_until',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'days'        => 'array',
            'valid_from'  => 'date',
            'valid_until' => 'date',
            'active'      => 'boolean',
        ];
    }

    public function effectiveColor(): string
    {
        return '#14569E';
    }

    public function rgbaColor(float $alpha = 0.15): string
    {
        $hex = ltrim($this->effectiveColor(), '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "rgba({$r}, {$g}, {$b}, {$alpha})";
    }

    public function dayLabels(): string
    {
        $names = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'];

        return implode(', ', array_map(
            fn (int $d) => $names[$d] ?? (string) $d,
            $this->days,
        ));
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

final class StepGoal extends Model
{
    protected $fillable = ['steps', 'effective_from'];

    protected function casts(): array
    {
        return [
            'steps' => 'integer',
            'effective_from' => 'date',
        ];
    }

    public static function current(): int
    {
        return self::query()
            ->where('effective_from', '<=', now()->toDateString())
            ->orderByDesc('effective_from')
            ->first()?->steps ?? 10000;
    }

    public static function forDate(Carbon $date): int
    {
        return self::query()
            ->where('effective_from', '<=', $date->toDateString())
            ->orderByDesc('effective_from')
            ->first()?->steps ?? 10000;
    }
}

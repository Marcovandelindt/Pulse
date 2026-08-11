<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\HealthEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HealthEntry extends Model
{
    /** @use HasFactory<HealthEntryFactory> */
    use HasFactory;

    protected $fillable = ['date', 'steps', 'notes'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'steps' => 'integer',
        ];
    }

    public function meetsStepGoal(int $goal): bool
    {
        return $this->steps !== null && $this->steps >= $goal;
    }

    public function scopeRecent(Builder $query, int $limit = 10): Builder
    {
        return $query->orderByDesc('date')->limit($limit);
    }

    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('date', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    public function scopeLastWeek(Builder $query): Builder
    {
        return $query->whereBetween('date', [
            now()->subWeek()->startOfWeek(),
            now()->subWeek()->endOfWeek(),
        ]);
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('date', now()->month)
            ->whereYear('date', now()->year);
    }

    public function scopeLastMonth(Builder $query): Builder
    {
        return $query->whereMonth('date', now()->subMonth()->month)
            ->whereYear('date', now()->subMonth()->year);
    }

    public function scopeBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('date', [$start, $end]);
    }

    public function scopeWithSteps(Builder $query): Builder
    {
        return $query->whereNotNull('steps');
    }
}

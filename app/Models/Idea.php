<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IdeaPriority;
use App\Enums\IdeaStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Idea extends Model
{
    protected $fillable = [
        'title',
        'description',
        'module',
        'priority',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'priority' => IdeaPriority::class,
            'status'   => IdeaStatus::class,
        ];
    }

    public function scopeByStatus(Builder $query, IdeaStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNot('status', IdeaStatus::Done);
    }
}

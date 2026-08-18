<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\BacklogStatus;
use Illuminate\Database\Eloquent\Builder;

trait HasBacklogStatus
{
    public function scopeInBacklog(Builder $query): Builder
    {
        return $query->whereNotNull('backlog_status');
    }

    public function scopeWantToPlay(Builder $query): Builder
    {
        return $query->where('backlog_status', BacklogStatus::NotStarted->value);
    }

    public function scopePlaying(Builder $query): Builder
    {
        return $query->where('backlog_status', BacklogStatus::InProgress->value);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('backlog_status', BacklogStatus::Completed->value);
    }

    public function scopeDropped(Builder $query): Builder
    {
        return $query->where('backlog_status', BacklogStatus::Dropped->value);
    }
}

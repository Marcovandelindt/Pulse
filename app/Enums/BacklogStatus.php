<?php

declare(strict_types=1);

namespace App\Enums;

enum BacklogStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case OnHold = 'on_hold';
    case Dropped = 'dropped';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not Started',
            self::InProgress => 'In Progress',
            self::Completed => 'Completed',
            self::OnHold => 'On Hold',
            self::Dropped => 'Dropped',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NotStarted => 'gray',
            self::InProgress => 'blue',
            self::Completed => 'green',
            self::OnHold => 'yellow',
            self::Dropped => 'red',
        };
    }
}

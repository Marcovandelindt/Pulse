<?php

declare(strict_types=1);

namespace App\Enums;

enum IdeaStatus: string
{
    case Idea       = 'idea';
    case Planned    = 'planned';
    case InProgress = 'in_progress';
    case Done       = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Idea       => 'Idea',
            self::Planned    => 'Planned',
            self::InProgress => 'In Progress',
            self::Done       => 'Done',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Idea       => 'gray',
            self::Planned    => 'blue',
            self::InProgress => 'yellow',
            self::Done       => 'green',
        };
    }

    public function next(): self
    {
        return match ($this) {
            self::Idea       => self::Planned,
            self::Planned    => self::InProgress,
            self::InProgress => self::Done,
            self::Done       => self::Done,
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum EventType: string
{
    case Event    = 'event';
    case Holiday  = 'holiday';
    case Reminder = 'reminder';

    public function label(): string
    {
        return match($this) {
            self::Event    => 'Event',
            self::Holiday  => 'Holiday',
            self::Reminder => 'Reminder',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Event    => '#6366f1',
            self::Holiday  => '#10b981',
            self::Reminder => '#f59e0b',
        };
    }

    public function bgClass(): string
    {
        return match($this) {
            self::Event    => 'calendar-pill--event',
            self::Holiday  => 'calendar-pill--holiday',
            self::Reminder => 'calendar-pill--reminder',
        };
    }
}

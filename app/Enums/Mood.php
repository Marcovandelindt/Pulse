<?php

declare(strict_types=1);

namespace App\Enums;

enum Mood: string
{
    case Great   = 'great';
    case Good    = 'good';
    case Neutral = 'neutral';
    case Bad     = 'bad';
    case Terrible = 'terrible';

    public function label(): string
    {
        return match($this) {
            self::Great    => 'Great',
            self::Good     => 'Good',
            self::Neutral  => 'Neutral',
            self::Bad      => 'Bad',
            self::Terrible => 'Terrible',
        };
    }

    public function emoji(): string
    {
        return match($this) {
            self::Great    => '😄',
            self::Good     => '🙂',
            self::Neutral  => '😐',
            self::Bad      => '😕',
            self::Terrible => '😞',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Great    => '#4ade80',
            self::Good     => '#86efac',
            self::Neutral  => '#94a3b8',
            self::Bad      => '#fb923c',
            self::Terrible => '#f87171',
        };
    }
}

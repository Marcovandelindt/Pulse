<?php

declare(strict_types=1);

namespace App\Enums;

enum PlayMode: string
{
    case SinglePlayer = 'single_player';
    case Multiplayer = 'multiplayer';
    case Coop = 'coop';
    case Mixed = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::SinglePlayer => 'Single Player',
            self::Multiplayer => 'Multiplayer',
            self::Coop => 'Co-op',
            self::Mixed => 'Mixed',
        };
    }
}

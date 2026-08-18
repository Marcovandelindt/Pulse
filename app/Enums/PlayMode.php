<?php

declare(strict_types=1);

namespace App\Enums;

enum PlayMode: string
{
    case SinglePlayer = 'single_player';
    case Multiplayer = 'multiplayer';
    case Coop = 'coop';

    public function label(): string
    {
        return match ($this) {
            self::SinglePlayer => 'Single Player',
            self::Multiplayer => 'Multiplayer',
            self::Coop => 'Co-op',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::SinglePlayer => '🎮',
            self::Multiplayer  => '👥',
            self::Coop         => '🤝',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SinglePlayer => 'blue',
            self::Multiplayer  => 'purple',
            self::Coop         => 'green',
        };
    }
}

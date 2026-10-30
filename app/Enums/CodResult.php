<?php

declare(strict_types=1);

namespace App\Enums;

enum CodResult: string
{
    case Victory = 'victory';
    case Defeat = 'defeat';

    public function label(): string
    {
        return match ($this) {
            self::Victory => 'Victory',
            self::Defeat => 'Defeat',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Victory => 'green',
            self::Defeat => 'red',
        };
    }
}

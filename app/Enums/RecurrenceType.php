<?php

declare(strict_types=1);

namespace App\Enums;

enum RecurrenceType: string
{
    case None    = 'none';
    case Weekly  = 'weekly';
    case Monthly = 'monthly';
    case Yearly  = 'yearly';

    public function label(): string
    {
        return match($this) {
            self::None    => 'Does not repeat',
            self::Weekly  => 'Every week',
            self::Monthly => 'Every month',
            self::Yearly  => 'Every year',
        };
    }
}

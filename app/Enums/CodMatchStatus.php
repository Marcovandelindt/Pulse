<?php

declare(strict_types=1);

namespace App\Enums;

enum CodMatchStatus: string
{
    case Confirmed = 'confirmed';
    case PendingReview = 'pending_review';
}

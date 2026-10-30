<?php

declare(strict_types=1);

namespace App\Actions\PlayStation\Cod;

enum ProcessResultStatus: string
{
    case Confirmed = 'confirmed';
    case PendingReview = 'pending_review';
    case MatchDuplicate = 'match_duplicate';
    case ScreenshotDuplicate = 'screenshot_duplicate';

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Saved',
            self::PendingReview => 'Needs review',
            self::MatchDuplicate => 'Already exists',
            self::ScreenshotDuplicate => 'Screenshot already uploaded',
        };
    }
}

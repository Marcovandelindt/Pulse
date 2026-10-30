<?php

declare(strict_types=1);

namespace App\Actions\PlayStation\Cod;

use App\Enums\CodMatchStatus;
use App\Models\CodMatch;
use App\Models\CodScreenshot;

final readonly class ProcessResult
{
    /** @param list<string> $validationErrors */
    public function __construct(
        public ProcessResultStatus $status,
        public ?CodMatch $match,
        public CodScreenshot $screenshot,
        public array $validationErrors = [],
    ) {}

    public function isActionable(): bool
    {
        return $this->status !== ProcessResultStatus::ScreenshotDuplicate
            && $this->status !== ProcessResultStatus::MatchDuplicate;
    }

    public function matchStatus(): ?CodMatchStatus
    {
        return match ($this->status) {
            ProcessResultStatus::Confirmed => CodMatchStatus::Confirmed,
            ProcessResultStatus::PendingReview => CodMatchStatus::PendingReview,
            default => null,
        };
    }
}

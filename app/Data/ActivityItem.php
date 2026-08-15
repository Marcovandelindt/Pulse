<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\Carbon;

final readonly class ActivityItem
{
    public function __construct(
        public string $type,
        public string $title,
        public string $subtitle,
        public ?string $imageUrl,
        public Carbon $occurredAt,
        public bool $isPinned,
        /** @var list<string> */
        public array $episodes = [],
    ) {}
}

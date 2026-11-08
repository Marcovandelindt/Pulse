<?php

declare(strict_types=1);

namespace App\Actions\Waze;

final readonly class ImportResult
{
    public function __construct(
        public int $imported,
        public int $skipped,
        public int $enriched,
    ) {}
}

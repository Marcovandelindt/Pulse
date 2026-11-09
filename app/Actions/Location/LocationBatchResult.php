<?php

declare(strict_types=1);

namespace App\Actions\Location;

final readonly class LocationBatchResult
{
    /**
     * @param  list<DeviceBatchRange>  $deviceRanges
     */
    public function __construct(
        public int $inserted,
        public array $deviceRanges,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Actions\Location;

use Carbon\Carbon;

final readonly class DeviceBatchRange
{
    public function __construct(
        public string $deviceId,
        public Carbon $from,
        public Carbon $to,
    ) {}
}

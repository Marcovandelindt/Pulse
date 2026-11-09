<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Location\LocationProcessorService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ProcessLocationBatch implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $deviceId,
        private readonly Carbon $from,
        private readonly Carbon $to,
    ) {}

    public function handle(LocationProcessorService $processor): void
    {
        $processor->processForDevice($this->deviceId, $this->from, $this->to);
    }
}

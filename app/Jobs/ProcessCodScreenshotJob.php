<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\PlayStation\Cod\ProcessCodScreenshot;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ProcessCodScreenshotJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(
        public readonly string $storedPath,
        public readonly string $filename,
        public readonly string $fileHash,
        public readonly Carbon $takenAt,
    ) {}

    public function handle(ProcessCodScreenshot $processor): void
    {
        $processor->handle(
            storedPath: $this->storedPath,
            filename: $this->filename,
            fileHash: $this->fileHash,
            takenAt: $this->takenAt,
        );
    }
}

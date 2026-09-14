<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Nintendo\SyncNintendoData;
use App\Services\Nintendo\NintendoService;
use Illuminate\Console\Command;

final class SyncNintendo extends Command
{
    protected $signature = 'nintendo:sync';

    protected $description = 'Sync Nintendo Switch play records via nxapi';

    public function handle(NintendoService $service, SyncNintendoData $action): int
    {
        if (! $service->isAvailable()) {
            $this->error('nxapi is not installed or not in PATH. Run: npm install -g nxapi');

            return self::FAILURE;
        }

        $this->info('Fetching Nintendo Switch play records...');

        try {
            $count = $action->handle();
            $this->info("Synced {$count} daily records.");
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

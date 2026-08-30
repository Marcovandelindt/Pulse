<?php

declare(strict_types=1);

namespace App\Console\Commands\PlayStation;

use App\Actions\PlayStation\SyncGamingPresenceAction;
use Illuminate\Console\Command;

final class SyncGamingPresenceCommand extends Command
{
    protected $signature   = 'gaming:sync-presence';
    protected $description = 'Poll PSN presence API and update active gaming session';

    public function handle(SyncGamingPresenceAction $action): void
    {
        $action->handle();
    }
}

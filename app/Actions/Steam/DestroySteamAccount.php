<?php

declare(strict_types=1);

namespace App\Actions\Steam;

use App\Models\SteamAccount;

final class DestroySteamAccount
{
    public function handle(SteamAccount $account): void
    {
        $wasActive = $account->is_active;
        $account->delete();

        if ($wasActive) {
            SteamAccount::first()?->activate();
        }
    }
}

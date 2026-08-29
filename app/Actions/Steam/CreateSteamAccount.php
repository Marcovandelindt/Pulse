<?php

declare(strict_types=1);

namespace App\Actions\Steam;

use App\Models\SteamAccount;

final class CreateSteamAccount
{
    /** @param array{label: string, steam_id: string, api_key: string} $validated */
    public function handle(array $validated): SteamAccount
    {
        $isFirst = SteamAccount::count() === 0;

        return SteamAccount::create([
            ...$validated,
            'is_active' => $isFirst,
        ]);
    }
}

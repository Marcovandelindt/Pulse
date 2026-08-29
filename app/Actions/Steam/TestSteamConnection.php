<?php

declare(strict_types=1);

namespace App\Actions\Steam;

use App\Models\SteamAccount;
use App\Services\Steam\SteamApiService;

final class TestSteamConnection
{
    public function __construct(
        private readonly SteamApiService $api,
    ) {}

    /** @return array{success: bool, game_count?: int, error?: string} */
    public function handle(SteamAccount $account): array
    {
        return $this->api->testConnection($account);
    }
}

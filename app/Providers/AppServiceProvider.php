<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\PlayStationGame;
use App\Models\SteamGame;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::useTailwind();

        Relation::enforceMorphMap([
            'playstation' => PlayStationGame::class,
            'steam'       => SteamGame::class,
        ]);
    }
}

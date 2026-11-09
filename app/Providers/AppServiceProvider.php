<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\PlayStationGame;
use App\Models\SteamGame;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Process\Process;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::useTailwind();

        RateLimiter::for('nominatim', fn () => Limit::perSecond(1));

        if ($this->app->runningInConsole() && in_array('serve', $_SERVER['argv'] ?? [])) {
            $scheduler = new Process([PHP_BINARY, 'artisan', 'schedule:work'], base_path());
            $scheduler->setTimeout(null);
            $scheduler->start();

            register_shutdown_function(fn () => $scheduler->stop());
        }

        Relation::enforceMorphMap([
            'playstation' => PlayStationGame::class,
            'steam' => SteamGame::class,
        ]);
    }
}

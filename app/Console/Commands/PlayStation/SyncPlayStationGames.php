<?php

declare(strict_types=1);

namespace App\Console\Commands\PlayStation;

use App\Services\PlayStation\PlayStationScraperService;
use Illuminate\Console\Command;

final class SyncPlayStationGames extends Command
{
    protected $signature = 'playstation:sync {username? : PSN username} {--cookie= : Session cookie} {--save-cookie : Save cookie to .env}';

    protected $description = 'Sync PlayStation games from PS-Timetracker';

    public function handle(PlayStationScraperService $scraper): void
    {
        $username = $this->argument('username') ?? config('services.playstation.username');
        $cookie = $this->option('cookie') ?? config('services.playstation.cookie');

        if (! $username) {
            $this->error('No username provided. Set PLAYSTATION_USERNAME in .env or pass it as argument.');

            return;
        }

        if ($cookie) {
            $scraper->setSessionCookie($cookie);

            if ($this->option('save-cookie')) {
                $this->saveCookieToEnv($cookie);
                $this->info('Cookie saved to .env.');
            }
        }

        $synced = $scraper->syncGames($username);
        $this->info("Synced {$synced} games.");
    }

    private function saveCookieToEnv(string $cookie): void
    {
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        if (str_contains($envContent, 'PLAYSTATION_COOKIE=')) {
            file_put_contents($envPath, preg_replace(
                '/^PLAYSTATION_COOKIE=.*/m',
                'PLAYSTATION_COOKIE='.$cookie,
                $envContent,
            ));
        } else {
            file_put_contents($envPath, $envContent."\nPLAYSTATION_COOKIE={$cookie}");
        }
    }
}

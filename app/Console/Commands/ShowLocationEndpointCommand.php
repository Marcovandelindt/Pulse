<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

final class ShowLocationEndpointCommand extends Command
{
    protected $signature = 'location:endpoint';

    protected $description = 'Show the Overland endpoint URL and token to paste into the app';

    public function handle(): int
    {
        $token = config('services.overland.token');
        $appUrl = rtrim((string) config('app.url'), '/');
        $localIp = $this->resolveLocalIp();

        $this->newLine();
        $this->line('<fg=yellow>Overland endpoint setup</>');
        $this->line(str_repeat('─', 60));

        if (empty($token)) {
            $this->error('OVERLAND_TOKEN is not set in .env — add a secret value first.');
            $this->newLine();

            return self::FAILURE;
        }

        $this->info('Paste one of these URLs into Overland → Settings → Server URL:');
        $this->newLine();

        if ($localIp !== null) {
            $port = parse_url($appUrl, PHP_URL_PORT) ?? 8000;
            $this->line("  <fg=cyan>http://{$localIp}:{$port}/api/location/overland</>");
        }

        $this->line("  <fg=cyan>{$appUrl}/api/location/overland</>");
        $this->newLine();
        $this->line('Token (paste into Overland → Settings → Access token):');
        $this->line("  <fg=green>{$token}</>");
        $this->newLine();
        $this->line('<fg=gray>Tip: make sure Pulse is reachable on the network (--host=0.0.0.0)</>');
        $this->newLine();

        return self::SUCCESS;
    }

    private function resolveLocalIp(): ?string
    {
        $ip = gethostbyname((string) gethostname());

        // gethostbyname returns the input unchanged on failure
        return filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : null;
    }
}

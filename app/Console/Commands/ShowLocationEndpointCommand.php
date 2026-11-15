<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

final class ShowLocationEndpointCommand extends Command
{
    protected $signature = 'location:endpoint';

    protected $description = 'Show the OwnTracks endpoint URL and credentials to paste into the app';

    public function handle(): int
    {
        $user = config('services.owntracks.user');
        $password = config('services.owntracks.password');
        $appUrl = rtrim((string) config('app.url'), '/');
        $localIp = $this->resolveLocalIp();

        $this->newLine();
        $this->line('<fg=yellow>OwnTracks endpoint setup</>');
        $this->line(str_repeat('─', 60));

        if (empty($user) || empty($password)) {
            $this->error('OWNTRACKS_USER or OWNTRACKS_PASSWORD is not set in .env — add values first.');
            $this->newLine();

            return self::FAILURE;
        }

        $this->info('Paste one of these URLs into OwnTracks → Settings → Host:');
        $this->newLine();

        if ($localIp !== null) {
            $port = parse_url($appUrl, PHP_URL_PORT) ?? 8000;
            $this->line("  <fg=cyan>http://{$localIp}:{$port}/api/location/owntracks</>");
        }

        $this->line("  <fg=cyan>{$appUrl}/api/location/owntracks</>");
        $this->newLine();
        $this->line('Credentials (OwnTracks → Settings → Identification):');
        $this->line("  Username: <fg=green>{$user}</>");
        $this->line("  Password: <fg=green>{$password}</>");
        $this->newLine();
        $this->line('<fg=gray>Also set: Mode = HTTP, Path = /api/location/owntracks</>');
        $this->line('<fg=gray>Tip: make sure Pulse is reachable on the network (--host=0.0.0.0)</>');
        $this->newLine();

        return self::SUCCESS;
    }

    private function resolveLocalIp(): ?string
    {
        $ip = gethostbyname((string) gethostname());

        return filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : null;
    }
}

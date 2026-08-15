<?php

declare(strict_types=1);

namespace App\Console\Commands\PlayStation;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

final class DebugPlayStation extends Command
{
    protected $signature = 'playstation:debug {username? : PSN username} {--cookie= : Session cookie value (_my_app_session)}';

    protected $description = 'Debug PS-Timetracker scraper — dumps response and found elements';

    public function handle(): void
    {
        $username = $this->argument('username') ?? config('services.playstation.username');
        $cookie = $this->option('cookie');

        if (! $username) {
            $this->error('No username provided.');

            return;
        }

        $this->testUrl(
            'Profile (games)',
            "https://ps-timetracker.com/profile/{$username}",
            $cookie,
        );

        $this->newLine();

        $this->testUrl(
            'Playtimes page 1 (sessions)',
            "https://ps-timetracker.com/profile/{$username}/playtimes?page=1",
            $cookie,
        );
    }

    private function testUrl(string $label, string $url, ?string $cookie): void
    {
        $this->info("[{$label}]");
        $this->line("URL: {$url}");

        $headers = $cookie ? ['Cookie' => "_my_app_session={$cookie}"] : [];

        $response = Http::withoutVerifying()->withHeaders($headers)->get($url);

        $this->line("Status: {$response->status()}");
        $this->line('Response size: '.strlen($response->body()).' bytes');

        if (! $response->successful()) {
            $this->error('Request failed.');
            $this->line(substr($response->body(), 0, 500));

            return;
        }

        $html = $response->body();

        if (str_contains($html, 'Your PSN-Name') && str_contains($html, 'Your Code')) {
            $this->warn('Cookie is invalid or expired — login page detected.');

            return;
        }

        $crawler = new Crawler($html);

        $title = $crawler->filter('title')->count() > 0 ? $crawler->filter('title')->text() : '—';
        $this->line("Page title: {$title}");

        $tableCount = $crawler->filter('table')->count();
        $this->line("Tables found: {$tableCount}");

        $crawler->filter('table')->each(function (Crawler $table, int $i) {
            $rows = $table->filter('tbody tr')->count();
            $this->line("  Table #{$i}: {$rows} rows");

            $table->filter('tbody tr')->each(function (Crawler $row, int $r) {
                if ($r >= 3) {
                    return;
                }
                $cells = $row->filter('td');
                $values = [];
                $cells->each(function (Crawler $cell, int $c) use (&$values) {
                    $values[] = "#{$c}: ".trim(substr($cell->text(''), 0, 30));
                });
                $this->line('    Row '.$r.': '.implode(' | ', $values));
            });
        });

        if ($tableCount === 0) {
            $this->warn('No tables found. First 2000 chars:');
            $this->line(substr(strip_tags($html), 0, 2000));
        }

        $hasNext = $crawler->filter('a[rel="next"]')->count() > 0;
        $this->line('Has next page: '.($hasNext ? 'yes' : 'no'));
    }
}

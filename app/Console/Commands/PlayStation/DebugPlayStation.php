<?php

declare(strict_types=1);

namespace App\Console\Commands\PlayStation;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

final class DebugPlayStation extends Command
{
    protected $signature = 'playstation:debug {username? : PSN username} {--cookie= : Session cookie} {--game= : Scrape session page for this game name}';

    protected $description = 'Debug PS-Timetracker scraper — dumps response and found elements';

    public function handle(): void
    {
        $username = $this->argument('username') ?? config('services.playstation.username');
        $cookie = $this->option('cookie');
        $gameName = $this->option('game');

        if (! $username) {
            $this->error('No username provided.');

            return;
        }

        $url = $gameName
            ? "https://ps-timetracker.com/user/{$username}/game/".urlencode($gameName)
            : "https://ps-timetracker.com/user/{$username}";

        $this->info("Fetching: {$url}");

        $response = Http::withoutVerifying()
            ->withHeaders(['Cookie' => $cookie ?? ''])
            ->get($url);

        $this->line("Status: {$response->status()}");
        $this->line('Response size: '.strlen($response->body()).' bytes');

        if (! $response->successful()) {
            $this->error('Request failed.');
            $this->line(substr($response->body(), 0, 500));

            return;
        }

        $crawler = new Crawler($response->body());

        // Show page title
        $title = $crawler->filter('title')->count() > 0 ? $crawler->filter('title')->text() : '—';
        $this->line("Page title: {$title}");

        // Find all tables
        $tableCount = $crawler->filter('table')->count();
        $this->line("Tables found: {$tableCount}");

        $crawler->filter('table')->each(function (Crawler $table, int $i) {
            $rows = $table->filter('tbody tr')->count();
            $this->line("  Table #{$i}: {$rows} rows");

            $table->filter('tbody tr')->each(function (Crawler $row, int $r) {
                if ($r >= 3) {
                    return;
                } // only show first 3 rows
                $cells = $row->filter('td');
                $values = [];
                $cells->each(function (Crawler $cell) use (&$values) {
                    $values[] = trim(substr($cell->text(''), 0, 40));
                });
                $this->line('    Row '.$r.': ['.implode('] [', $values).']');
            });
        });

        // Dump first 2000 chars of body if no tables found
        if ($tableCount === 0) {
            $this->warn('No tables found. First 2000 chars of response:');
            $this->line(substr(strip_tags($response->body()), 0, 2000));
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Person;
use App\Services\Tmdb\TMDBClient;
use Illuminate\Console\Command;

final class FetchEnglishPersonNames extends Command
{
    protected $signature = 'people:fetch-english-names';

    protected $description = 'Fetch English/romanized names from TMDB for people with non-ASCII names';

    public function handle(TMDBClient $client): int
    {
        $people = Person::whereNull('name_en')
            ->get()
            ->filter(fn (Person $p): bool => (bool) preg_match('/[^\x00-\x7F]/', $p->name));

        $this->info("Found {$people->count()} people with non-ASCII names.");

        $bar = $this->output->createProgressBar($people->count());
        $bar->start();

        foreach ($people as $person) {
            $data = $client->getWithLanguage("/person/{$person->tmdb_id}", 'en-US');

            $englishName = $data['name'] ?? null;

            if ($englishName && $englishName !== $person->name) {
                $person->update(['name_en' => $englishName]);
            }

            $bar->advance();
            usleep(50_000); // stay well within TMDB's 40 req/10s limit
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done.');

        return self::SUCCESS;
    }
}

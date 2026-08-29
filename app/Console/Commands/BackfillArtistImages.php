<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Artist;
use App\Services\Spotify\SpotifyService;
use Illuminate\Console\Command;

class BackfillArtistImages extends Command
{
    protected $signature   = 'spotify:backfill-artists';
    protected $description = 'Fetch missing image/genres/popularity for artists with incomplete data';

    public function handle(SpotifyService $spotify): int
    {
        $artists = Artist::whereNull('image_url')->get(['id', 'spotify_artist_id', 'name']);

        if ($artists->isEmpty()) {
            $this->info('All artists already have an image.');

            return self::SUCCESS;
        }

        $this->info("Found {$artists->count()} artists without an image. Fetching...");
        $bar     = $this->output->createProgressBar($artists->count());
        $updated = 0;

        foreach ($artists->chunk(50) as $chunk) {
            $ids      = $chunk->pluck('spotify_artist_id')->implode(',');
            $response = $spotify->get('/artists', ['ids' => $ids]);

            if ($response === null) {
                $this->newLine();
                $this->warn('Spotify API returned null — stopping. Try again later.');

                return self::FAILURE;
            }

            foreach ($response['artists'] as $data) {
                if ($data === null) {
                    $bar->advance();
                    continue;
                }

                Artist::where('spotify_artist_id', $data['id'])->update([
                    'image_url'  => $data['images'][0]['url'] ?? null,
                    'genres'     => $data['genres'] ?? [],
                    'popularity' => $data['popularity'] ?? null,
                ]);

                $updated++;
                $bar->advance();
            }

            usleep(200_000);
        }

        $bar->finish();
        $this->newLine();
        $this->info("Done — updated {$updated} artists.");

        return self::SUCCESS;
    }
}

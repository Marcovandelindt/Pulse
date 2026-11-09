<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\GeocodePlaceJob;
use App\Models\Place;
use Illuminate\Console\Command;

final class GeocodeAllPlacesCommand extends Command
{
    protected $signature = 'location:geocode
        {--force : Re-geocode places that already have a name}';

    protected $description = 'Dispatch geocoding jobs for all unnamed places';

    public function handle(): int
    {
        $query = Place::query();

        if (! $this->option('force')) {
            $query->whereNull('name');
        }

        $places = $query->get();

        if ($places->isEmpty()) {
            $this->info('No places to geocode.');

            return self::SUCCESS;
        }

        foreach ($places as $place) {
            GeocodePlaceJob::dispatch($place->id);
        }

        $this->info("Dispatched {$places->count()} geocoding job(s). Run the queue worker to process them.");

        return self::SUCCESS;
    }
}

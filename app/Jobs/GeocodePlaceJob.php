<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Place;
use App\Services\Location\NominatimService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;

final class GeocodePlaceJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $placeId,
    ) {}

    /** @return list<RateLimited> */
    public function middleware(): array
    {
        return [new RateLimited('nominatim')];
    }

    public function handle(NominatimService $nominatim): void
    {
        $place = Place::find($this->placeId);

        if ($place === null || $place->name !== null) {
            return;
        }

        $name = $nominatim->reverse($place->latitude, $place->longitude);

        if ($name !== null) {
            $place->update(['name' => $name]);
        }
    }
}

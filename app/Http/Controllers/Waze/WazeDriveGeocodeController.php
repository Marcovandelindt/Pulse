<?php

declare(strict_types=1);

namespace App\Http\Controllers\Waze;

use App\Http\Controllers\Controller;
use App\Models\WazeDrive;
use App\Services\Waze\NominatimGeocoderService;
use Illuminate\Http\RedirectResponse;

final class WazeDriveGeocodeController extends Controller
{
    public function __invoke(WazeDrive $drive, NominatimGeocoderService $geocoder): RedirectResponse
    {
        $updates = [];

        if ($drive->origin_name === null && $drive->origin_lat !== null && $drive->origin_lon !== null) {
            $name = $geocoder->reverse($drive->origin_lat, $drive->origin_lon);

            if ($name !== null) {
                $updates['origin_name'] = $name;
            }
        }

        if ($drive->destination_name === null && $drive->destination_lat !== null && $drive->destination_lon !== null) {
            if ($updates !== []) {
                sleep(1);
            }

            $name = $geocoder->reverse($drive->destination_lat, $drive->destination_lon);

            if ($name !== null) {
                $updates['destination_name'] = $name;
            }
        }

        if ($updates !== []) {
            $drive->update($updates);
        }

        return redirect()->back();
    }
}

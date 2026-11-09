<?php

declare(strict_types=1);

namespace App\Services\Location;

use Illuminate\Support\Facades\Http;

final class NominatimService
{
    private const string BASE_URL = 'https://nominatim.openstreetmap.org/reverse';

    public function reverse(float $lat, float $lon): ?string
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Pulse/1.0 (personal dashboard; marcovdlindt1998@gmail.com)',
            'Accept-Language' => 'nl,en',
        ])->get(self::BASE_URL, [
            'lat' => $lat,
            'lon' => $lon,
            'format' => 'json',
            'zoom' => 18,
            'addressdetails' => 1,
        ]);

        if (! $response->successful()) {
            return null;
        }

        /** @var array<string, mixed> $data */
        $data = $response->json();

        /** @var array<string, string> $address */
        $address = is_array($data['address'] ?? null) ? $data['address'] : [];

        return $this->buildName($address);
    }

    /** @param array<string, string> $address */
    private function buildName(array $address): ?string
    {
        foreach (['amenity', 'shop', 'tourism', 'leisure', 'office', 'historic'] as $key) {
            if (! empty($address[$key])) {
                $name = $address[$key];
                if (! empty($address['road'])) {
                    $name .= ', '.$address['road'];
                }

                return $name;
            }
        }

        if (! empty($address['road'])) {
            $name = $address['road'];
            if (! empty($address['house_number'])) {
                $name .= ' '.$address['house_number'];
            }

            return $name;
        }

        foreach (['suburb', 'neighbourhood', 'quarter', 'village', 'town', 'city'] as $key) {
            if (! empty($address[$key])) {
                return $address[$key];
            }
        }

        return null;
    }
}

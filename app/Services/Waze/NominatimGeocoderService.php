<?php

declare(strict_types=1);

namespace App\Services\Waze;

use Illuminate\Support\Facades\Http;

final class NominatimGeocoderService
{
    private const string BASE_URL = 'https://nominatim.openstreetmap.org/reverse';

    public function reverse(float $lat, float $lon): ?string
    {
        $response = Http::withHeaders([
            'User-Agent' => 'Pulse/1.0 (marcovdlindt1998@gmail.com)',
            'Accept-Language' => 'nl,en',
        ])->get(self::BASE_URL, [
            'lat' => $lat,
            'lon' => $lon,
            'format' => 'json',
            'addressdetails' => 1,
        ]);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        if (! is_array($data) || ! isset($data['address'])) {
            return null;
        }

        return $this->formatAddress($data['address']);
    }

    /** @param array<string, string> $address */
    private function formatAddress(array $address): string
    {
        $road = $address['road'] ?? $address['pedestrian'] ?? $address['path'] ?? null;
        $number = $address['house_number'] ?? null;
        $city = $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['municipality'] ?? null;

        $street = $road !== null
            ? rtrim($road.($number !== null ? ' '.$number : ''))
            : null;

        if ($street !== null && $city !== null) {
            return $street.', '.$city;
        }

        return $street ?? $city ?? ($address['display_name'] ?? 'Unknown');
    }
}

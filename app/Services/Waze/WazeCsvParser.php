<?php

declare(strict_types=1);

namespace App\Services\Waze;

use Carbon\Carbon;

final class WazeCsvParser
{
    private const string DRIVE_SECTION = 'Drive summary';

    /** @return list<array{driven_at: Carbon, origin_name: string|null, origin_lat: float|null, origin_lon: float|null, destination_name: string|null, destination_lat: float|null, destination_lon: float|null, distance_km: float|null}> */
    public function parse(string $csvContent): array
    {
        $csvContent = ltrim($csvContent, "\xEF\xBB\xBF");

        $drives = [];
        $lines = explode("\n", $csvContent);
        $inSection = false;
        $skipHeader = false;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === self::DRIVE_SECTION) {
                $inSection = true;
                $skipHeader = true;

                continue;
            }

            if ($inSection && $skipHeader) {
                $skipHeader = false;

                continue;
            }

            if ($inSection && $line === '') {
                $inSection = false;

                continue;
            }

            if (! $inSection) {
                continue;
            }

            $drive = $this->parseLine($line);

            if ($drive !== null) {
                $drives[] = $drive;
            }
        }

        return $drives;
    }

    /** @return array{driven_at: Carbon, origin_name: string|null, origin_lat: float|null, origin_lon: float|null, destination_name: string|null, destination_lat: float|null, destination_lon: float|null, distance_km: float|null}|null */
    private function parseLine(string $line): ?array
    {
        $parts = explode(',', $line, 3);

        if (count($parts) !== 3) {
            return null;
        }

        [$dateStr, $source, $destination] = $parts;

        $drivenAt = Carbon::parse(trim($dateStr))->utc();

        [$originName, $originLat, $originLon] = $this->extractLocation(trim($source));
        [$destinationName, $destinationLat, $destinationLon] = $this->extractLocation(trim($destination));

        $distanceKm = $this->calculateDistance($originLat, $originLon, $destinationLat, $destinationLon);

        return [
            'driven_at' => $drivenAt,
            'origin_name' => $originName,
            'origin_lat' => $originLat,
            'origin_lon' => $originLon,
            'destination_name' => $destinationName,
            'destination_lat' => $destinationLat,
            'destination_lon' => $destinationLon,
            'distance_km' => $distanceKm,
        ];
    }

    /** @return array{string|null, float|null, float|null} */
    private function extractLocation(string $value): array
    {
        if ($value === '') {
            return [null, null, null];
        }

        preg_match('/\(([0-9.\-]+)\s+([0-9.\-]+)\)/', $value, $matches);

        if (count($matches) !== 3) {
            return [$value, null, null];
        }

        $lon = (float) $matches[1];
        $lat = (float) $matches[2];
        $lastParen = strrpos($value, '(');
        $name = $lastParen !== false ? trim(substr($value, 0, $lastParen)) : $value;

        return [$name ?: null, $lat, $lon];
    }

    private function calculateDistance(?float $lat1, ?float $lon1, ?float $lat2, ?float $lon2): ?float
    {
        if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) {
            return null;
        }

        $earthRadius = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return round($earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
    }
}

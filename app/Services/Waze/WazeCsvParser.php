<?php

declare(strict_types=1);

namespace App\Services\Waze;

use Carbon\Carbon;

final class WazeCsvParser
{
    private const string DRIVE_SECTION = 'Drive summary';

    private const string LOCATION_SECTION = 'Location details';

    /** @return list<array{driven_at: Carbon, origin_name: string|null, origin_lat: float|null, origin_lon: float|null, destination_name: string|null, destination_lat: float|null, destination_lon: float|null, distance_km: float|null}> */
    public function parse(string $csvContent): array
    {
        $drives = [];

        foreach ($this->extractSection($csvContent, self::DRIVE_SECTION) as $line) {
            $drive = $this->parseDriveLine($line);

            if ($drive !== null) {
                $drives[] = $drive;
            }
        }

        return $drives;
    }

    /** @return list<array{driven_at: Carbon, ended_at: Carbon, origin_lat: float, origin_lon: float, destination_lat: float, destination_lon: float, distance_km: float}> */
    public function parseLocationTracks(string $csvContent): array
    {
        $tracks = [];

        foreach ($this->extractSection($csvContent, self::LOCATION_SECTION) as $line) {
            $track = $this->parseLocationLine($line);

            if ($track !== null) {
                $tracks[] = $track;
            }
        }

        return $tracks;
    }

    /** @return list<string> */
    private function extractSection(string $csvContent, string $header): array
    {
        $csvContent = ltrim($csvContent, "\xEF\xBB\xBF");

        $lines = explode("\n", $csvContent);
        $inSection = false;
        $skipHeader = false;
        $result = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === $header) {
                $inSection = true;
                $skipHeader = true;

                continue;
            }

            if ($inSection && $skipHeader) {
                $skipHeader = false;

                continue;
            }

            if ($inSection && $line === '') {
                break;
            }

            if ($inSection) {
                $result[] = $line;
            }
        }

        return $result;
    }

    /** @return array{driven_at: Carbon, origin_name: string|null, origin_lat: float|null, origin_lon: float|null, destination_name: string|null, destination_lat: float|null, destination_lon: float|null, distance_km: float|null}|null */
    private function parseDriveLine(string $line): ?array
    {
        $parts = explode(',', $line, 3);

        if (count($parts) !== 3) {
            return null;
        }

        [$dateStr, $source, $destination] = $parts;

        $drivenAt = Carbon::parse(trim($dateStr))->setTimezone('Europe/Amsterdam');

        [$originName, $originLat, $originLon] = $this->extractLocation(trim($source));
        [$destinationName, $destinationLat, $destinationLon] = $this->extractLocation(trim($destination));

        $distanceKm = $this->haversine($originLat, $originLon, $destinationLat, $destinationLon);

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

    /** @return array{driven_at: Carbon, ended_at: Carbon, origin_lat: float, origin_lon: float, destination_lat: float, destination_lon: float, distance_km: float}|null */
    private function parseLocationLine(string $line): ?array
    {
        // Format: "2026-11-04 14:18:06 UTC,timestamp+00(lon lat)|..."
        $parts = explode(',', $line, 2);

        if (count($parts) !== 2) {
            return null;
        }

        [$dateStr, $coordinatesStr] = $parts;

        $drivenAt = Carbon::parse(trim($dateStr))->setTimezone('Europe/Amsterdam');

        /** @var list<array{float, float}> $coords [lon, lat] */
        $coords = [];
        $lastTimestamp = null;

        foreach (explode('|', $coordinatesStr) as $point) {
            if (preg_match('/^(.+?)\(([0-9.\-]+)\s+([0-9.\-]+)\)/', trim($point), $m)) {
                $coords[] = [(float) $m[2], (float) $m[3]];
                $lastTimestamp = trim($m[1]);
            }
        }

        if (count($coords) < 2) {
            return null;
        }

        $endedAt = $lastTimestamp !== null
            ? Carbon::parse($lastTimestamp)->setTimezone('Europe/Amsterdam')
            : $drivenAt;

        $totalDistance = 0.0;

        for ($i = 0; $i < count($coords) - 1; $i++) {
            $dist = $this->haversine($coords[$i][1], $coords[$i][0], $coords[$i + 1][1], $coords[$i + 1][0]);

            if ($dist !== null) {
                $totalDistance += $dist;
            }
        }

        $last = count($coords) - 1;

        return [
            'driven_at' => $drivenAt,
            'ended_at' => $endedAt,
            'origin_lat' => $coords[0][1],
            'origin_lon' => $coords[0][0],
            'destination_lat' => $coords[$last][1],
            'destination_lon' => $coords[$last][0],
            'distance_km' => round($totalDistance, 2),
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

    private function haversine(?float $lat1, ?float $lon1, ?float $lat2, ?float $lon2): ?float
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

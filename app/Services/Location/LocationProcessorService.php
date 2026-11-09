<?php

declare(strict_types=1);

namespace App\Services\Location;

use App\Models\LocationPoint;
use App\Models\LocationTrip;
use App\Models\LocationVisit;
use App\Models\Place;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

final class LocationProcessorService
{
    private const int EARTH_RADIUS_METERS = 6371000;

    public function processForDevice(string $deviceId, Carbon $from, Carbon $to): void
    {
        $bufferHours = (int) config('location.processing.processing_buffer_hours', 2);
        $windowFrom = $from->copy()->subHours($bufferHours);
        $windowTo = $to->copy()->addHours($bufferHours);

        $points = LocationPoint::query()
            ->where('device_id', $deviceId)
            ->whereBetween('recorded_at', [$windowFrom, $windowTo])
            ->where(function (Builder $q): void {
                $maxAccuracy = (int) config('location.processing.max_accuracy_meters', 100);
                $q->whereNull('horizontal_accuracy')
                    ->orWhere('horizontal_accuracy', '<=', $maxAccuracy);
            })
            ->orderBy('recorded_at')
            ->get();

        if ($points->isEmpty()) {
            return;
        }

        // Delete existing results in the window to ensure idempotency
        LocationVisit::where('device_id', $deviceId)
            ->where('started_at', '>=', $windowFrom)
            ->where('ended_at', '<=', $windowTo)
            ->delete();

        LocationTrip::where('device_id', $deviceId)
            ->where('started_at', '>=', $windowFrom)
            ->where('ended_at', '<=', $windowTo)
            ->delete();

        /** @var array<int, LocationPoint> $pointsArray */
        $pointsArray = $points->values()->all();

        [$visitSegments, $tripSegments] = $this->detectSegments($pointsArray);

        foreach ($visitSegments as $segment) {
            $place = $this->findOrCreatePlace(
                $segment['centroid_lat'],
                $segment['centroid_lon'],
            );

            LocationVisit::create([
                'place_id' => $place->id,
                'device_id' => $deviceId,
                'started_at' => $segment['started_at'],
                'ended_at' => $segment['ended_at'],
                'centroid_latitude' => $segment['centroid_lat'],
                'centroid_longitude' => $segment['centroid_lon'],
                'point_count' => $segment['point_count'],
            ]);
        }

        foreach ($tripSegments as $segment) {
            LocationTrip::create([
                'device_id' => $deviceId,
                'started_at' => $segment['started_at'],
                'ended_at' => $segment['ended_at'],
                'distance_meters' => $segment['distance_meters'],
                'dominant_motion' => $segment['dominant_motion'],
                'point_count' => $segment['point_count'],
                'polyline' => $segment['polyline'],
            ]);
        }
    }

    /**
     * Segments an ordered list of points into visits (stationary clusters)
     * and trips (movement between visits).
     *
     * Returns [visits, trips], each as a list of attribute arrays.
     *
     * @param  array<int, LocationPoint>  $points
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    private function detectSegments(array $points): array
    {
        $visitRadius = (int) config('location.processing.visit_radius_meters', 100);
        $minDurationMinutes = (int) config('location.processing.min_visit_duration_minutes', 10);

        $n = count($points);
        $visitRanges = []; // list of [startIdx, endIdx]
        $i = 0;

        while ($i < $n) {
            $anchorLat = (float) $points[$i]->latitude;
            $anchorLon = (float) $points[$i]->longitude;
            $j = $i + 1;

            // Expand cluster: include points within visit_radius of the anchor
            while ($j < $n) {
                $dist = $this->haversine(
                    $anchorLat,
                    $anchorLon,
                    (float) $points[$j]->latitude,
                    (float) $points[$j]->longitude,
                );

                if ($dist <= $visitRadius) {
                    $j++;
                } else {
                    break;
                }
            }

            $clusterEnd = $j - 1;
            $durationMinutes = $points[$i]->recorded_at->diffInMinutes($points[$clusterEnd]->recorded_at);

            if ($clusterEnd > $i && $durationMinutes >= $minDurationMinutes) {
                $visitRanges[] = [$i, $clusterEnd];
                $i = $j;
            } else {
                $i++;
            }
        }

        // Build visit data from ranges
        $visits = [];
        foreach ($visitRanges as [$startIdx, $endIdx]) {
            $clusterPoints = array_slice($points, $startIdx, $endIdx - $startIdx + 1);
            $visits[] = $this->buildVisitData($clusterPoints);
        }

        // Build trip data from points that fall between visits
        $inVisit = array_fill(0, $n, false);
        foreach ($visitRanges as [$startIdx, $endIdx]) {
            for ($k = $startIdx; $k <= $endIdx; $k++) {
                $inVisit[$k] = true;
            }
        }

        $trips = [];
        $currentTripPoints = [];

        for ($i = 0; $i < $n; $i++) {
            if (! $inVisit[$i]) {
                $currentTripPoints[] = $points[$i];
            } elseif (count($currentTripPoints) >= 2) {
                $trips[] = $this->buildTripData($currentTripPoints);
                $currentTripPoints = [];
            } else {
                $currentTripPoints = [];
            }
        }

        if (count($currentTripPoints) >= 2) {
            $trips[] = $this->buildTripData($currentTripPoints);
        }

        return [$visits, $trips];
    }

    /**
     * @param  array<int, LocationPoint>  $points
     * @return array<string, mixed>
     */
    private function buildVisitData(array $points): array
    {
        $latSum = 0.0;
        $lonSum = 0.0;
        $count = count($points);

        foreach ($points as $point) {
            $latSum += (float) $point->latitude;
            $lonSum += (float) $point->longitude;
        }

        return [
            'started_at' => $points[0]->recorded_at,
            'ended_at' => $points[$count - 1]->recorded_at,
            'centroid_lat' => $latSum / $count,
            'centroid_lon' => $lonSum / $count,
            'point_count' => $count,
        ];
    }

    /**
     * @param  array<int, LocationPoint>  $points
     * @return array<string, mixed>
     */
    private function buildTripData(array $points): array
    {
        $count = count($points);
        $distance = 0.0;

        for ($i = 1; $i < $count; $i++) {
            $distance += $this->haversine(
                (float) $points[$i - 1]->latitude,
                (float) $points[$i - 1]->longitude,
                (float) $points[$i]->latitude,
                (float) $points[$i]->longitude,
            );
        }

        return [
            'started_at' => $points[0]->recorded_at,
            'ended_at' => $points[$count - 1]->recorded_at,
            'distance_meters' => $distance,
            'dominant_motion' => $this->detectDominantMotion($points),
            'point_count' => $count,
            'polyline' => $this->buildPolyline($points),
        ];
    }

    /**
     * Returns the most common motion type across the given points, falling back
     * to a speed-based estimate when motion data is absent.
     *
     * @param  array<int, LocationPoint>  $points
     */
    private function detectDominantMotion(array $points): string
    {
        $counts = ['driving' => 0, 'walking' => 0, 'cycling' => 0, 'other' => 0];

        foreach ($points as $point) {
            if (empty($point->motion)) {
                continue;
            }

            $matched = false;
            foreach ($point->motion as $type) {
                $type = strtolower((string) $type);

                if (str_contains($type, 'driving') || str_contains($type, 'automotive')) {
                    $counts['driving']++;
                    $matched = true;
                    break;
                }

                if (str_contains($type, 'walking') || str_contains($type, 'running')) {
                    $counts['walking']++;
                    $matched = true;
                    break;
                }

                if (str_contains($type, 'cycling') || str_contains($type, 'bike')) {
                    $counts['cycling']++;
                    $matched = true;
                    break;
                }
            }

            if (! $matched) {
                $counts['other']++;
            }
        }

        arsort($counts);
        $top = (string) array_key_first($counts);

        if ($counts[$top] > 0) {
            return $top;
        }

        // Fall back to average speed (m/s)
        $speeds = array_filter(array_map(fn ($p) => $p->speed, $points));
        $avgSpeed = count($speeds) > 0
            ? array_sum($speeds) / count($speeds)
            : 0.0;

        if ($avgSpeed > 10.0) {
            return 'driving';    // > 36 km/h
        }

        if ($avgSpeed > 2.0) {
            return 'cycling';    // > 7 km/h
        }

        if ($avgSpeed > 0.5) {
            return 'walking';    // > 1.8 km/h
        }

        return 'other';
    }

    /**
     * Builds a downsampled polyline from the given points.
     *
     * @param  array<int, LocationPoint>  $points
     * @return list<array{float, float}>
     */
    private function buildPolyline(array $points): array
    {
        $maxPoints = (int) config('location.processing.trip_polyline_max_points', 300);

        if (count($points) <= $maxPoints) {
            return array_values(
                array_map(fn ($p) => [(float) $p->latitude, (float) $p->longitude], $points),
            );
        }

        $result = [];
        $step = count($points) / $maxPoints;

        for ($i = 0; $i < $maxPoints; $i++) {
            $idx = (int) round($i * $step);
            $p = $points[min($idx, count($points) - 1)];
            $result[] = [(float) $p->latitude, (float) $p->longitude];
        }

        // Always include the last point
        $lastPoint = $points[count($points) - 1];
        $result[] = [(float) $lastPoint->latitude, (float) $lastPoint->longitude];

        return $result;
    }

    /**
     * Returns an existing Place within merge_radius of the given coordinates,
     * or creates a new one.
     */
    private function findOrCreatePlace(float $lat, float $lon): Place
    {
        $mergeRadius = (int) config('location.processing.place_merge_radius_meters', 150);

        $nearby = Place::all()->first(
            fn (Place $place) => $this->haversine($lat, $lon, $place->latitude, $place->longitude) <= $mergeRadius,
        );

        if ($nearby !== null) {
            $nearby->increment('visit_count');

            return $nearby;
        }

        return Place::create([
            'latitude' => $lat,
            'longitude' => $lon,
            'visit_count' => 1,
        ]);
    }

    /**
     * Haversine formula — returns distance in metres between two coordinates.
     */
    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $φ1 = deg2rad($lat1);
        $φ2 = deg2rad($lat2);
        $Δφ = deg2rad($lat2 - $lat1);
        $Δλ = deg2rad($lon2 - $lon1);

        $a = sin($Δφ / 2) ** 2 + cos($φ1) * cos($φ2) * sin($Δλ / 2) ** 2;

        return self::EARTH_RADIUS_METERS * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}

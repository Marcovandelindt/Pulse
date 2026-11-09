<?php

declare(strict_types=1);

namespace App\Actions\Location;

use App\Models\LocationPoint;
use App\Models\LocationTrip;
use App\Models\LocationVisit;
use Carbon\Carbon;

final class GetLocationWidgetData
{
    /** @return array{weekKm: float, lastVisitName: string|null, lastSeenAt: Carbon|null} */
    public function handle(): array
    {
        $weekKm = round(
            (float) LocationTrip::where('started_at', '>=', now()->startOfWeek())->sum('distance_meters') / 1000,
            1
        );

        $lastVisit = LocationVisit::with('place')
            ->orderByDesc('ended_at')
            ->first();

        $lastPointAt = LocationPoint::query()
            ->max('recorded_at');

        return [
            'weekKm' => $weekKm,
            'lastVisitName' => $lastVisit?->place?->name,
            'lastSeenAt' => is_string($lastPointAt) ? Carbon::parse($lastPointAt) : null,
        ];
    }
}

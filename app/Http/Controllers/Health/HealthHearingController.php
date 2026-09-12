<?php

declare(strict_types=1);

namespace App\Http\Controllers\Health;

use App\Http\Controllers\Controller;
use App\Models\HealthEntry;
use Illuminate\View\View;

final class HealthHearingController extends Controller
{
    public function index(): View
    {
        $history = HealthEntry::where(function ($q) {
            $q->whereNotNull('headphone_audio_exposure_db')
              ->orWhereNotNull('environmental_audio_exposure_db');
        })
            ->orderByDesc('date')
            ->limit(90)
            ->get()
            ->reverse()
            ->values();

        $latest = $history->last();

        $avgHeadphones   = $history->whereNotNull('headphone_audio_exposure_db')->avg('headphone_audio_exposure_db');
        $avgEnvironment  = $history->whereNotNull('environmental_audio_exposure_db')->avg('environmental_audio_exposure_db');

        $headphoneChart = [
            'labels' => $history->whereNotNull('headphone_audio_exposure_db')->map(fn ($e) => $e->date->format('d M'))->values()->all(),
            'values' => $history->whereNotNull('headphone_audio_exposure_db')->map(fn ($e) => round((float) $e->headphone_audio_exposure_db, 1))->values()->all(),
        ];

        $environmentChart = [
            'labels' => $history->whereNotNull('environmental_audio_exposure_db')->map(fn ($e) => $e->date->format('d M'))->values()->all(),
            'values' => $history->whereNotNull('environmental_audio_exposure_db')->map(fn ($e) => round((float) $e->environmental_audio_exposure_db, 1))->values()->all(),
        ];

        return view('pages.health.hearing', compact(
            'history', 'latest',
            'avgHeadphones', 'avgEnvironment',
            'headphoneChart', 'environmentChart',
        ));
    }
}

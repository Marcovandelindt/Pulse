<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

final class SettingsController extends Controller
{
    public function index(): View
    {
        $stored = Setting::getWeatherLocation();

        return view('pages.settings.index', [
            'weatherCity' => $stored['city'] ?? config('services.weather.city'),
            'weatherLat'  => $stored['lat']  ?? config('services.weather.lat'),
            'weatherLon'  => $stored['lon']  ?? config('services.weather.lon'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'weather_city' => ['required', 'string', 'max:100'],
            'weather_lat'  => ['required', 'numeric', 'between:-90,90'],
            'weather_lon'  => ['required', 'numeric', 'between:-180,180'],
        ]);

        Setting::setWeatherLocation(
            city: $validated['weather_city'],
            lat:  (string) $validated['weather_lat'],
            lon:  (string) $validated['weather_lon'],
        );

        Cache::forget('weather.current');

        return redirect()->route('settings.index')->with('success', 'Settings saved.');
    }
}

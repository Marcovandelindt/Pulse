<?php

declare(strict_types=1);

namespace App\Services\Weather;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class WeatherService
{
    private const BASE_URL  = 'https://api.open-meteo.com/v1/forecast';
    private const CACHE_TTL = 1800; // 30 minutes

    private const CONDITIONS = [
        0  => ['label' => 'Clear sky',          'class' => 'clear'],
        1  => ['label' => 'Mainly clear',        'class' => 'clear'],
        2  => ['label' => 'Partly cloudy',       'class' => 'partly-cloudy'],
        3  => ['label' => 'Overcast',            'class' => 'cloudy'],
        45 => ['label' => 'Foggy',               'class' => 'fog'],
        48 => ['label' => 'Icy fog',             'class' => 'fog'],
        51 => ['label' => 'Light drizzle',       'class' => 'drizzle'],
        53 => ['label' => 'Drizzle',             'class' => 'drizzle'],
        55 => ['label' => 'Dense drizzle',       'class' => 'drizzle'],
        56 => ['label' => 'Freezing drizzle',    'class' => 'drizzle'],
        57 => ['label' => 'Freezing drizzle',    'class' => 'drizzle'],
        61 => ['label' => 'Light rain',          'class' => 'rain'],
        63 => ['label' => 'Rain',                'class' => 'rain'],
        65 => ['label' => 'Heavy rain',          'class' => 'rain'],
        66 => ['label' => 'Freezing rain',       'class' => 'rain'],
        67 => ['label' => 'Freezing rain',       'class' => 'rain'],
        71 => ['label' => 'Light snowfall',      'class' => 'snow'],
        73 => ['label' => 'Snowfall',            'class' => 'snow'],
        75 => ['label' => 'Heavy snowfall',      'class' => 'snow'],
        77 => ['label' => 'Snow grains',         'class' => 'snow'],
        80 => ['label' => 'Light showers',       'class' => 'showers'],
        81 => ['label' => 'Showers',             'class' => 'showers'],
        82 => ['label' => 'Heavy showers',       'class' => 'showers'],
        85 => ['label' => 'Snow showers',        'class' => 'snow'],
        86 => ['label' => 'Heavy snow showers',  'class' => 'snow'],
        95 => ['label' => 'Thunderstorm',        'class' => 'thunder'],
        96 => ['label' => 'Thunderstorm',        'class' => 'thunder'],
        99 => ['label' => 'Heavy thunderstorm',  'class' => 'thunder'],
    ];

    public function getCurrent(): ?array
    {
        $stored = Setting::getWeatherLocation();
        $lat    = $stored['lat']  ?? config('services.weather.lat');
        $lon    = $stored['lon']  ?? config('services.weather.lon');
        $city   = $stored['city'] ?? config('services.weather.city', 'Unknown');

        if (! $lat || ! $lon) {
            return null;
        }

        if (Cache::has('weather.current')) {
            return Cache::get('weather.current');
        }

        try {
            $response = Http::timeout(5)->get(self::BASE_URL, [
                'latitude'        => $lat,
                'longitude'       => $lon,
                'current'         => 'temperature_2m,relative_humidity_2m,apparent_temperature,weather_code,wind_speed_10m,is_day',
                'wind_speed_unit' => 'kmh',
                'timezone'        => 'auto',
            ]);

            if (! $response->successful()) {
                return null;
            }

            $current = $response->json('current');

            if (! is_array($current)) {
                return null;
            }

            $code      = (int) ($current['weather_code'] ?? 0);
            $condition = self::CONDITIONS[$code] ?? ['label' => 'Unknown', 'class' => 'clear'];

            $result = [
                'city'                 => $city,
                'temperature'          => (int) round((float) ($current['temperature_2m'] ?? 0)),
                'apparent_temperature' => (int) round((float) ($current['apparent_temperature'] ?? 0)),
                'humidity'             => (int) ($current['relative_humidity_2m'] ?? 0),
                'wind_speed'           => (int) round((float) ($current['wind_speed_10m'] ?? 0)),
                'weather_code'         => $code,
                'condition'            => $condition['label'],
                'condition_class'      => $condition['class'],
                'is_day'               => (bool) ($current['is_day'] ?? true),
            ];

            Cache::put('weather.current', $result, self::CACHE_TTL);

            return $result;
        } catch (\Throwable) {
            return null;
        }
    }
}

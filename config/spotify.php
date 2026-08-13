<?php

declare(strict_types=1);

return [
    'client_id' => env('SPOTIFY_CLIENT_ID'),
    'client_secret' => env('SPOTIFY_CLIENT_SECRET'),
    'redirect_uri' => env('SPOTIFY_REDIRECT_URI'),
    'cache_duration' => (int) env('SPOTIFY_CACHE_DURATION', 1440),

    'image_sizes' => [
        'small' => 64,
        'medium' => 300,
        'large' => 640,
    ],
];

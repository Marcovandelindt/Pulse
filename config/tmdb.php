<?php

declare(strict_types=1);

return [
    'api_key' => env('TMDB_API_KEY'),
    'base_url' => 'https://api.themoviedb.org/3',
    'image_base_url' => 'https://image.tmdb.org/t/p/',
    'cache_duration' => (int) env('TMDB_CACHE_DURATION', 1440),
    'region' => 'NL',
    'language' => 'nl-NL',

    'poster_sizes' => ['small' => 'w185', 'medium' => 'w342', 'large' => 'w500'],
    'backdrop_sizes' => ['small' => 'w300', 'medium' => 'w780', 'large' => 'w1280'],
    'profile_sizes' => ['small' => 'w45',  'medium' => 'w185', 'large' => 'h632'],
];

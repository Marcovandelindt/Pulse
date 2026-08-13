<?php

declare(strict_types=1);

use App\Enums\SpotifyScope;

return [
    'client_id' => env('SPOTIFY_CLIENT_ID'),
    'client_secret' => env('SPOTIFY_CLIENT_SECRET'),
    'redirect_uri' => env('SPOTIFY_REDIRECT_URI'),
    'default_scopes' => [
        SpotifyScope::UserReadEmail->value,
        SpotifyScope::UserReadPrivate->value,
        SpotifyScope::UserReadRecentlyPlayed->value,
        SpotifyScope::UserReadCurrentlyPlaying->value,
        SpotifyScope::UserReadPlaybackState->value,
    ],
];

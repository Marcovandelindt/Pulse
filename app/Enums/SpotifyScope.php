<?php

declare(strict_types=1);

namespace App\Enums;

enum SpotifyScope: string
{
    case UserReadEmail = 'user-read-email';
    case UserReadPrivate = 'user-read-private';
    case UserReadRecentlyPlayed = 'user-read-recently-played';
    case UserReadCurrentlyPlaying = 'user-read-currently-playing';
    case UserReadPlaybackState = 'user-read-playback-state';
}

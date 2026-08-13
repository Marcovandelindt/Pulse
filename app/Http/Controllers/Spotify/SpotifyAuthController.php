<?php

declare(strict_types=1);

namespace App\Http\Controllers\Spotify;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Spotify\SpotifyAuthService;
use App\Services\Spotify\SpotifyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SpotifyAuthController extends Controller
{
    public function __construct(
        private readonly SpotifyAuthService $authService,
        private readonly SpotifyService $spotifyService,
    ) {}

    public function redirect(): RedirectResponse
    {
        return redirect()->away($this->authService->getAuthorizeUrl());
    }

    public function callback(Request $request): RedirectResponse
    {
        $code = $request->string('code')->toString();
        $state = $request->string('state')->toString();

        $credentials = $this->authService->handleCallback($code, $state);
        Setting::storeSpotifyCredentials($credentials);

        return redirect()->route('music.index')->with('success', 'Spotify connected!');
    }

    public function disconnect(): RedirectResponse
    {
        $this->spotifyService->disconnect();

        return redirect()->back()->with('success', 'Spotify disconnected.');
    }
}

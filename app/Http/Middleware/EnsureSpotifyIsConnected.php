<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Spotify\SpotifyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureSpotifyIsConnected
{
    public function __construct(
        private readonly SpotifyService $spotify,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->spotify->isConnected()) {
            return redirect()->route('spotify.auth')->with('warning', 'Connect your Spotify account to continue.');
        }

        return $next($request);
    }
}

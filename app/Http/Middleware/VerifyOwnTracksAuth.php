<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class VerifyOwnTracksAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $configured_user = config('services.owntracks.user');
        $configured_password = config('services.owntracks.password');

        if (empty($configured_user) || empty($configured_password)) {
            return response()->json(['error' => 'OwnTracks credentials not configured'], 401);
        }

        $provided_user = $request->getUser();
        $provided_password = $request->getPassword();

        if (
            $provided_user === null ||
            $provided_password === null ||
            ! hash_equals((string) $configured_user, $provided_user) ||
            ! hash_equals((string) $configured_password, $provided_password)
        ) {
            return response()->json(['error' => 'Unauthorized'], 401)
                ->header('WWW-Authenticate', 'Basic realm="Pulse"');
        }

        return $next($request);
    }
}

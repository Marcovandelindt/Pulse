<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class VerifyOverlandToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $configured = config('services.overland.token');

        // Reject requests when no token is configured
        if (empty($configured)) {
            return response()->json(['error' => 'Overland token not configured'], 401);
        }

        $provided = $this->resolveToken($request);

        if ($provided === null || ! hash_equals((string) $configured, $provided)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }

    private function resolveToken(Request $request): ?string
    {
        // Overland sends the token as Bearer header or ?access_token= query param
        $bearer = $request->bearerToken();
        if ($bearer !== null) {
            return $bearer;
        }

        $query = $request->query('access_token');

        return is_string($query) ? $query : null;
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $user = $accessToken->tokenable;

        if (!$user) {
            return response()->json(['message' => 'User not found'], 401);
        }

        // Load the user's business
        $user->load('business');

        // Set the current access token on the user
        $user->withAccessToken($accessToken);

        // Set the authenticated user
        auth()->setUser($user);

        return $next($request);
    }
}

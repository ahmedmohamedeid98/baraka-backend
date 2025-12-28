<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class OptionalAuth
{
    /**
     * Handle an incoming request.
     * 
     * This middleware attempts to authenticate the user if a Bearer token is present,
     * but allows the request to proceed even if no token is provided.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if Authorization header is present
        $token = $request->bearerToken();

        if ($token) {

            // Attempt to find the token and authenticate the user
            $accessToken = PersonalAccessToken::findToken($token);

            if ($accessToken && !$accessToken->tokenable->trashed()) {
                // Set the authenticated user
                Auth::setUser($accessToken->tokenable);
                
                // Set the current access token
                $accessToken->tokenable->withAccessToken($accessToken);
            }
        }

        // Continue with the request (whether authenticated or not)
        return $next($request);
    }
}

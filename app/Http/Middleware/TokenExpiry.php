<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TokenExpiry
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->user()?->currentAccessToken();

        if ($token && $token->expires_at && now()->greaterThan($token->expires_at)) {
            $token->delete();
            return response()->json([
                'status'  => 401,
                'error'   => true,
                'message' => 'Session expired. Please login again.',
            ], 401);
        }

        return $next($request);
    }
}
<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateJwt
{
    public function __construct(protected JwtService $jwt)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Token tidak ditemukan.'], 401);
        }

        $payload = $this->jwt->verify($token);

        if (! $payload || empty($payload['sub'])) {
            return response()->json(['message' => 'Token tidak valid atau kedaluwarsa.'], 401);
        }

        $user = User::find($payload['sub']);

        if (! $user) {
            return response()->json(['message' => 'User tidak ditemukan.'], 401);
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
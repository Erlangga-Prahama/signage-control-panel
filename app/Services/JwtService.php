<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    protected string $secret;
    protected int $ttlMinutes;

    public function __construct()
    {
        $this->secret = (string) config('signage.jwt.secret');
        $this->ttlMinutes = (int) config('signage.jwt.ttl_minutes');
    }

    public function issue(User $user): string
    {
        $now = time();

        $payload = [
            'iss' => config('app.url'),
            'sub' => $user->id,
            'email' => $user->email,
            'iat' => $now,
            'exp' => $now + ($this->ttlMinutes * 60),
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function verify(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));

            return (array) $decoded;
        } catch (\Throwable) {
            return null;
        }
    }
}
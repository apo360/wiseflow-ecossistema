<?php

// app/Services/RefreshTokenService.php
namespace App\Services;

use App\Models\RefreshToken;
use Illuminate\Support\Str;

class RefreshTokenService
{
    public function create(int $userId, int $tenantId): string
    {
        // Apaga tokens antigos do mesmo user/tenant para evitar acumulação
        RefreshToken::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->delete();

        $token = Str::random(64);

        RefreshToken::create([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'token' => hash('sha256', $token), // armazena hash, devolve original
            'expires_at' => now()->addDays(14),
        ]);

        return $token;
    }

    public function validateAndRetrieve(string $token): ?RefreshToken
    {
        $hashed = hash('sha256', $token);
        $refreshToken = RefreshToken::where('token', $hashed)->first();

        if (!$refreshToken || $refreshToken->isExpired()) {
            return null;
        }

        return $refreshToken;
    }

    public function revoke(RefreshToken $refreshToken): void
    {
        $refreshToken->delete();
    }
}
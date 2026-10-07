<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class TokenRevocationService
{
    protected string $prefix = 'token_blacklist:';

    /**
     * Revogar um token específico.
     */
    public function revoke(string $jti, int $ttlSeconds): void
    {
        Redis::setex($this->prefix . $jti, $ttlSeconds, 'revoked');
    }

    /**
     * Verificar se um token foi revogado.
     */
    public function isRevoked(string $jti): bool
    {
        return Redis::exists($this->prefix . $jti) > 0;
    }

    /**
     * Revogar todos os tokens de um utilizador num tenant (opcional).
     * Nota: exige manter um mapeamento user:tenant -> jtis.
     * Para simplicidade, pode-se invalidar apenas o token atual.
     */
    public function revokeAllForUser(int $userId, int $tenantId): void
    {

        $key = "user_tokens:{$userId}:{$tenantId}";
        $jtiList = Redis::smembers($key);

        foreach ($jtiList as $jti) {
            // Coloca cada jti na blacklist com um TTL razoável (1 hora ou o tempo restante)
            // Como não sabemos o TTL exato de cada token, definimos um valor padrão (ex: 3600)
            // ou podemos obter o TTL do próprio token se tivermos essa informação.
            // Para simplificar, use um TTL fixo longo o suficiente.
            Redis::setex($this->prefix . $jti, 3600, 'revoked');
        }

        // Remove o conjunto para evitar revogações repetidas
        Redis::del($key);
    }
}
<?php

// app/Services/AuthService.php
namespace App\Services;

use App\Models\User;
use App\Services\CoreDatabaseService; // <-- alterado
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Redis;

class AuthService
{
    protected CoreDatabaseService $coreDb;

    public function __construct(CoreDatabaseService $coreDb)
    {
        $this->coreDb = $coreDb;
    }

    public function attemptLogin(string $email, string $password, ?string $tenantSlug = null): ?array
    {
        if (!$token = auth('api')->attempt(['email' => $email, 'password' => $password])) {
            return null;
        }

        $user = auth('api')->user();

        // 1. Obter tenants do utilizador via Core DB
        $tenants = $this->coreDb->getUserTenants($user->id);
        if (empty($tenants)) {
            auth('api')->logout();
            return null;
        }

        // 2. Determinar tenant activo
        $tenantId = $this->resolveTenantId($tenants, $tenantSlug);
        $tenant = collect($tenants)->firstWhere('id', $tenantId);

        // 3. Obter RBAC para este tenant via Core DB
        $rbac = $this->coreDb->getUserRbac($user->id, $tenantId);

        // 4. Construir claims customizadas
        $customClaims = [
            'iss' => config('jwt.iss', 'wiseflow-auth'),
            'aud' => 'wiseflow',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
            ],
            'tenant' => [
                'id' => $tenantId,
                'slug' => $tenant['slug'] ?? '',
            ],
            'roles' => $rbac['roles'] ?? [],
            'permissions' => $rbac['permissions'] ?? [],
        ];

        // 5. Emitir token com claims
        $accessToken = JWTAuth::claims($customClaims)->fromUser($user);

        // 🔹 Novo: registar jti no Redis para futura revogação em massa
        $payload = JWTAuth::setToken($accessToken)->getPayload();
        $jti = $payload->get('jti');
        $exp = $payload->get('exp');
        $ttl = $exp - time();

        Redis::sadd("user_tokens:{$user->id}:{$tenantId}", $jti);
        // Define um TTL máximo para a chave do conjunto, evitando acumulação eterna
        Redis::expire("user_tokens:{$user->id}:{$tenantId}", $ttl);

        return [
            'access_token' => $accessToken,
            'user' => $user,
            'tenant_id' => $tenantId,
        ];
    }

    protected function resolveTenantId(array $tenants, ?string $slug): int
    {
        if ($slug) {
            $tenant = collect($tenants)->firstWhere('slug', $slug);
            if (!$tenant) {
                throw new \Exception('Tenant inválido');
            }
            return $tenant['id'];
        }

        // Se apenas um tenant, usa esse; caso contrário, poderia exigir escolha
        if (count($tenants) === 1) {
            return $tenants[0]['id'];
        }

        throw new \Exception('Múltiplos tenants disponíveis; especifique o slug');
    }

}
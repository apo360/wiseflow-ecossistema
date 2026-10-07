<?php

namespace Wiseflow\JwtAuth\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;
use Wiseflow\JwtAuth\Exceptions\JwtConfigurationException;
use Wiseflow\JwtAuth\Exceptions\JwtInvalidTokenException;
use Wiseflow\JwtAuth\Exceptions\JwtUnauthorizedException;
use Wiseflow\JwtAuth\Services\JwtValidationService;
use Wiseflow\JwtAuth\Support\JsonErrorResponses;

class JwtAuthenticate
{
    use JsonErrorResponses;

    public function __construct(protected JwtValidationService $jwtValidationService) {}

    public function handle(Request $request, Closure $next)
    {
        $token = $this->extractToken($request);

        if (! $token) {
            return $this->unauthorized('Token de acesso não fornecido');
        }

        try {
            $decoded = $this->jwtValidationService->validateToken($token);
        } catch (JwtUnauthorizedException $exception) {
            return $this->unauthorized($exception->getMessage());
        } catch (JwtInvalidTokenException $exception) {
            return $this->unauthorized('Token inválido');
        } catch (JwtConfigurationException $exception) {
            Log::error('JWT configuração inválida', ['error' => $exception->getMessage()]);

            return response()->json(['error' => 'Erro interno de autenticação'], 500);
        } catch (Throwable $exception) {
            Log::error('JWT erro inesperado', ['error' => $exception->getMessage()]);

            return response()->json(['error' => 'Erro interno de autenticação'], 500);
        }

        if ($this->isTokenRevoked($decoded->jti)) {
            return $this->unauthorized('Token revogado');
        }

        $this->injectContext($request, $decoded);

        return $next($request);
    }

    /**
     * Extrai token do header ou cookie
     */
    protected function extractToken(Request $request): ?string
    {
        return $request->bearerToken()
            ?? $request->cookie(config('jwt.cookie_name', 'access_token'));
    }

    /**
     * Injeta contexto no request
     */
    protected function injectContext(Request $request, $decoded): void
    {
        $user = (array) $decoded->user;
        $tenant = (array) $decoded->tenant;
        $tenantId = $tenant['id'];
        $roles = $decoded->roles;
        $permissions = $decoded->permissions;

        $request->attributes->set('auth_user', $user);
        $request->attributes->set('auth_tenant', $tenant);
        $request->attributes->set('auth_tenant_id', $tenantId);
        $request->attributes->set('auth_roles', $roles);
        $request->attributes->set('auth_permissions', $permissions);
        $request->attributes->set('auth', [
            'user' => $user,
            'user_id' => $user['id'] ?? null,
            'tenant' => $tenant,
            'tenant_id' => $tenantId,
            'roles' => $roles,
            'permissions' => $permissions,
            'jti' => $decoded->jti,
        ]);
    }

    protected function isTokenRevoked(string $jti): bool
    {
        try {
            return Redis::exists(config('jwt.revocation_key_prefix', 'token_blacklist:').$jti) > 0;
        } catch (Throwable $exception) {
            Log::warning('JWT revogação indisponível', ['error' => $exception->getMessage()]);

            return ! config('jwt.revocation_fail_open', false);
        }
    }
}

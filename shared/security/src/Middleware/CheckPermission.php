<?php

namespace Wiseflow\JwtAuth\Middleware;

use Closure;
use Illuminate\Http\Request;
use Wiseflow\JwtAuth\Support\JsonErrorResponses;

class CheckPermission
{
    use JsonErrorResponses;

    /**
     * Verifica se o utilizador tem a permissão necessária
     */
    public function handle(Request $request, Closure $next, ...$requiredPermissions)
    {
        $auth = $request->attributes->get('auth');

        if (! $auth) {
            return $this->forbidden('Contexto de autenticação ausente');
        }

        $userPermissions = $auth['permissions'] ?? [];

        // Super admin bypass (opcional)
        if ($this->isSuperAdmin($auth)) {
            return $next($request);
        }

        // Verificar permissões
        foreach ($requiredPermissions as $permission) {
            if (in_array($permission, $userPermissions, true)) {
                return $next($request);
            }
        }

        return $this->forbidden('Sem permissão para executar esta acção');
    }

    /**
     * Verifica se o utilizador é super admin
     */
    protected function isSuperAdmin(array $auth): bool
    {
        $roles = $auth['roles'] ?? [];

        return in_array('super_admin', $roles, true);
    }
}

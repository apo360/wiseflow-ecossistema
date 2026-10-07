<?php

namespace Wiseflow\JwtAuth\Middleware;

use Closure;
use Illuminate\Http\Request;
use Wiseflow\JwtAuth\Support\JsonErrorResponses;

class CheckRole
{
    use JsonErrorResponses;

    public function handle(Request $request, Closure $next, ...$requiredRoles)
    {
        $auth = $request->attributes->get('auth');

        if (! $auth) {
            return $this->forbidden('Contexto de autenticação ausente');
        }

        $userRoles = $auth['roles'] ?? [];

        // Super admin bypass
        if (in_array('super_admin', $userRoles, true)) {
            return $next($request);
        }

        foreach ($requiredRoles as $role) {
            if (in_array($role, $userRoles, true)) {
                return $next($request);
            }
        }

        return $this->forbidden('Role não autorizada');
    }
}

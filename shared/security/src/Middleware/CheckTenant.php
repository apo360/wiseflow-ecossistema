<?php

namespace Wiseflow\JwtAuth\Middleware;

use Closure;
use Illuminate\Http\Request;
use Wiseflow\JwtAuth\Support\JsonErrorResponses;

class CheckTenant
{
    use JsonErrorResponses;

    public function handle(Request $request, Closure $next, $mode = 'strict')
    {
        $auth = $request->attributes->get('auth');
        $tenantId = $auth['tenant_id'] ?? $request->attributes->get('auth_tenant_id');

        if (! $auth || empty($tenantId)) {
            return $this->forbidden('Tenant não definido no token');
        }

        $requestTenant = $this->extractTenantFromRequest($request);

        // Modo strict → tem de coincidir
        if ($mode === 'strict' && $requestTenant && (string) $requestTenant !== (string) $tenantId) {
            return $this->forbidden('Acesso a tenant inválido');
        }

        // Injectar tenant no request para uso interno
        $request->attributes->set('tenant_id', $tenantId);

        return $next($request);
    }

    protected function extractTenantFromRequest(Request $request)
    {
        return $request->route('tenant_id')
            ?? $request->header('X-Tenant-ID')
            ?? $request->get('tenant_id');
    }
}

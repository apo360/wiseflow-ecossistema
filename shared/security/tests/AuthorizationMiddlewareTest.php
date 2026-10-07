<?php

namespace Wiseflow\JwtAuth\Tests;

use Illuminate\Http\Request;
use Wiseflow\JwtAuth\Middleware\CheckPermission;
use Wiseflow\JwtAuth\Middleware\CheckRole;
use Wiseflow\JwtAuth\Middleware\CheckTenant;

class AuthorizationMiddlewareTest extends TestCase
{
    public function test_check_tenant_uses_normalized_tenant_id(): void
    {
        $request = Request::create('/tenant', 'GET', ['tenant_id' => '20']);
        $request->attributes->set('auth', ['tenant_id' => 20]);

        $response = app(CheckTenant::class)->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(20, $request->attributes->get('tenant_id'));
    }

    public function test_check_tenant_accepts_route_tenant_id(): void
    {
        $request = Request::create('/tenants/20', 'GET');
        $request->attributes->set('auth', ['tenant_id' => 20]);
        $request->setRouteResolver(fn () => new class
        {
            public function parameter(string $name): ?string
            {
                return $name === 'tenant_id' ? '20' : null;
            }
        });

        $response = app(CheckTenant::class)->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_check_tenant_rejects_mismatched_header(): void
    {
        $request = Request::create('/tenant', 'GET', [], [], [], ['HTTP_X_TENANT_ID' => '99']);
        $request->attributes->set('auth', ['tenant_id' => 20]);

        $response = app(CheckTenant::class)->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_check_role_uses_strict_matching_and_super_admin_bypass(): void
    {
        $request = Request::create('/role', 'GET');
        $request->attributes->set('auth', ['roles' => ['super_admin']]);

        $response = app(CheckRole::class)->handle($request, fn () => response()->json(['ok' => true]), 'missing');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_check_permission_uses_strict_matching(): void
    {
        $request = Request::create('/permission', 'GET');
        $request->attributes->set('auth', [
            'roles' => [],
            'permissions' => ['123'],
        ]);

        $response = app(CheckPermission::class)->handle($request, fn () => response()->json(['ok' => true]), 123);

        $this->assertSame(403, $response->getStatusCode());
    }
}

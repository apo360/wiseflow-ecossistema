<?php

namespace Wiseflow\JwtAuth\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Wiseflow\JwtAuth\Middleware\JwtAuthenticate;

class JwtAuthenticateTest extends TestCase
{
    public function test_valid_token_injects_normalized_context(): void
    {
        Redis::shouldReceive('exists')->once()->andReturn(0);

        $request = Request::create('/secure', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->jwt(),
        ]);

        $response = app(JwtAuthenticate::class)->handle($request, function (Request $request) {
            $auth = $request->attributes->get('auth');

            $this->assertSame(10, $auth['user_id']);
            $this->assertSame(20, $auth['tenant_id']);
            $this->assertSame(['admin'], $request->attributes->get('auth_roles'));
            $this->assertSame(['users.view'], $request->attributes->get('auth_permissions'));

            return response()->json(['ok' => true]);
        });

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_missing_token_returns_unauthorized(): void
    {
        $request = Request::create('/secure', 'GET');

        $response = app(JwtAuthenticate::class)->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('unauthorized', $response->getData(true)['error']);
    }

    public function test_invalid_issuer_returns_unauthorized(): void
    {
        Redis::shouldReceive('exists')->never();

        $request = Request::create('/secure', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->jwt(['iss' => 'other']),
        ]);

        $response = app(JwtAuthenticate::class)->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_invalid_audience_returns_unauthorized(): void
    {
        Redis::shouldReceive('exists')->never();

        $request = Request::create('/secure', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->jwt(['aud' => 'other']),
        ]);

        $response = app(JwtAuthenticate::class)->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_expired_token_returns_unauthorized(): void
    {
        Redis::shouldReceive('exists')->never();

        $request = Request::create('/secure', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->jwt(['exp' => time() - 60]),
        ]);

        $response = app(JwtAuthenticate::class)->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_future_nbf_token_returns_unauthorized(): void
    {
        Redis::shouldReceive('exists')->never();

        $request = Request::create('/secure', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->jwt(['nbf' => time() + 60]),
        ]);

        $response = app(JwtAuthenticate::class)->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_invalid_signature_returns_unauthorized(): void
    {
        Redis::shouldReceive('exists')->never();

        $token = substr($this->jwt(), 0, -2).'xx';

        $request = Request::create('/secure', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ]);

        $response = app(JwtAuthenticate::class)->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_missing_required_claim_returns_unauthorized(): void
    {
        Redis::shouldReceive('exists')->never();

        $request = Request::create('/secure', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->jwt(['jti' => null]),
        ]);

        $response = app(JwtAuthenticate::class)->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_missing_public_key_returns_internal_authentication_error(): void
    {
        config()->set('jwt.public_key', null);

        $request = Request::create('/secure', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->jwt(),
        ]);

        $response = app(JwtAuthenticate::class)->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(500, $response->getStatusCode());
    }

    public function test_revoked_token_is_rejected(): void
    {
        Redis::shouldReceive('exists')->once()->andReturn(1);

        $request = Request::create('/secure', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->jwt(),
        ]);

        $response = app(JwtAuthenticate::class)->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_redis_failure_is_fail_closed_by_default(): void
    {
        Redis::shouldReceive('exists')->once()->andThrow(new \RuntimeException('redis down'));

        $request = Request::create('/secure', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->jwt(),
        ]);

        $response = app(JwtAuthenticate::class)->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_redis_failure_can_fail_open(): void
    {
        config()->set('jwt.revocation_fail_open', true);
        Redis::shouldReceive('exists')->once()->andThrow(new \RuntimeException('redis down'));

        $request = Request::create('/secure', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$this->jwt(),
        ]);

        $response = app(JwtAuthenticate::class)->handle($request, fn () => response()->json(['ok' => true]));

        $this->assertSame(200, $response->getStatusCode());
    }
}

<?php

namespace Wiseflow\JwtAuth\Tests;

use Wiseflow\JwtAuth\Middleware\AuditMiddleware;

class AuditMiddlewareTest extends TestCase
{
    public function test_sanitize_masks_nested_sensitive_fields(): void
    {
        $middleware = new class extends AuditMiddleware
        {
            public function exposeSanitize(array $data): array
            {
                return $this->sanitize($data);
            }
        };

        $sanitized = $middleware->exposeSanitize([
            'password' => 'secret',
            'profile' => [
                'api_key' => 'key',
                'name' => 'Ada',
            ],
        ]);

        $this->assertSame('***', $sanitized['password']);
        $this->assertSame('***', $sanitized['profile']['api_key']);
        $this->assertSame('Ada', $sanitized['profile']['name']);
    }

    public function test_payload_limit_omits_large_payloads(): void
    {
        config()->set('jwt.audit_payload_limit', 10);

        $middleware = new class extends AuditMiddleware
        {
            public function exposeLimitPayload(array $data): array|string
            {
                return $this->limitPayload($data);
            }
        };

        $this->assertSame('[payload omitted]', $middleware->exposeLimitPayload(['body' => 'too long']));
    }
}

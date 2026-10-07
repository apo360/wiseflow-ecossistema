<?php

namespace Wiseflow\JwtAuth\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuditMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $start) * 1000, 2);

        $auth = $request->attributes->get('auth', []);
        $user = $auth['user'] ?? [];

        $logData = [
            'timestamp' => now()->toDateTimeString(),
            'method' => $request->method(),
            'endpoint' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration,

            // contexto
            'user_id' => $auth['user_id'] ?? $user['id'] ?? null,
            'tenant_id' => $auth['tenant_id'] ?? null,

            // request
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        if (! in_array($request->method(), ['GET'], true)) {
            $logData['payload'] = $this->limitPayload($this->sanitize($request->all()));
        }

        Log::channel('audit')->info('API Access', $logData);

        return $response;
    }

    protected function sanitize(array $data): array
    {
        $sensitive = [
            'password',
            'password_confirmation',
            'token',
            'access_token',
            'refresh_token',
            'authorization',
            'secret',
            'api_key',
        ];

        foreach ($data as $field => $value) {
            if (in_array(strtolower((string) $field), $sensitive, true)) {
                $data[$field] = '***';

                continue;
            }

            if (is_array($value)) {
                $data[$field] = $this->sanitize($value);
            } elseif (is_string($value) && ! $this->isSafeStringForAudit($value)) {
                $data[$field] = '[omitted]';
            }
        }

        return $data;
    }

    protected function limitPayload(array $payload): array|string
    {
        $encoded = json_encode($payload);
        $limit = (int) config('jwt.audit_payload_limit', 8192);

        if ($encoded === false || strlen($encoded) > $limit) {
            return '[payload omitted]';
        }

        return $payload;
    }

    protected function isSafeStringForAudit(string $value): bool
    {
        return strlen($value) <= (int) config('jwt.audit_payload_limit', 8192)
            && preg_match('//u', $value) === 1;
    }
}

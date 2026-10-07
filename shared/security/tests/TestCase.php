<?php

namespace Wiseflow\JwtAuth\Tests;

use Firebase\JWT\JWT;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Wiseflow\JwtAuth\JwtAuthServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected string $privateKey;

    protected string $publicKey;

    protected function getPackageProviders($app): array
    {
        return [JwtAuthServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $key = $this->makeKeyPair();

        $this->privateKey = $key['private'];
        $this->publicKey = $key['public'];

        $app['config']->set('jwt.public_key', $this->publicKey);
        $app['config']->set('jwt.expected_iss', 'wiseflow-auth');
        $app['config']->set('jwt.expected_aud', 'wiseflow');
        $app['config']->set('jwt.algo', 'RS256');
        $app['config']->set('jwt.allowed_algos', ['RS256']);
        $app['config']->set('jwt.revocation_fail_open', false);
        $app['config']->set('jwt.audit_payload_limit', 8192);
    }

    protected function jwt(array $overrides = []): string
    {
        $now = time();
        $claims = array_replace([
            'iss' => 'wiseflow-auth',
            'aud' => 'wiseflow',
            'exp' => $now + 3600,
            'nbf' => $now - 1,
            'iat' => $now - 1,
            'jti' => 'test-jti',
            'user' => ['id' => 10, 'name' => 'Tester'],
            'tenant' => ['id' => 20, 'slug' => 'acme'],
            'roles' => ['admin'],
            'permissions' => ['users.view'],
        ], $overrides);

        return JWT::encode($claims, $this->privateKey, 'RS256');
    }

    /**
     * @return array{private:string,public:string}
     */
    private function makeKeyPair(): array
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($resource, $privateKey);
        $details = openssl_pkey_get_details($resource);

        return [
            'private' => $privateKey,
            'public' => $details['key'],
        ];
    }
}

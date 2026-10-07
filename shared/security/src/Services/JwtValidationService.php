<?php

namespace Wiseflow\JwtAuth\Services;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use UnexpectedValueException;
use Wiseflow\JwtAuth\Exceptions\JwtConfigurationException;
use Wiseflow\JwtAuth\Exceptions\JwtInvalidTokenException;
use Wiseflow\JwtAuth\Exceptions\JwtUnauthorizedException;

class JwtValidationService
{
    public function validateToken(string $token): object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->getPublicKey(), $this->getAlgorithm()));
        } catch (ExpiredException $exception) {
            throw new JwtUnauthorizedException('Token expirado', 0, $exception);
        } catch (UnexpectedValueException $exception) {
            throw new JwtInvalidTokenException('Token inválido', 0, $exception);
        }

        $this->validateClaims($decoded);

        return $decoded;
    }

    public function validateRequest(Request $request): object
    {
        $token = $request->bearerToken();

        if (! $token) {
            throw new JwtUnauthorizedException('Token de acesso não fornecido');
        }

        return $this->validateToken($token);
    }

    protected function getAlgorithm(): string
    {
        $algo = (string) config('jwt.algo', 'RS256');
        $allowed = (array) config('jwt.allowed_algos', ['RS256']);

        if (! in_array($algo, $allowed, true)) {
            throw new JwtConfigurationException('Algoritmo JWT não permitido');
        }

        return $algo;
    }

    protected function getPublicKey(): string
    {
        $key = config('jwt.public_key');

        if (! is_string($key) || trim($key) === '') {
            throw new JwtConfigurationException('Chave pública JWT não configurada');
        }

        if ($this->looksLikePem($key)) {
            return $key;
        }

        if ($this->looksLikePath($key)) {
            return $this->readPublicKeyFile($key);
        }

        throw new JwtConfigurationException('Chave pública JWT inválida');
    }

    protected function validateClaims(object $decoded): void
    {
        $requiredClaims = ['iss', 'aud', 'exp', 'nbf', 'iat', 'jti', 'user', 'tenant', 'roles', 'permissions'];

        foreach ($requiredClaims as $claim) {
            if (! property_exists($decoded, $claim)) {
                throw new JwtInvalidTokenException("Claim JWT obrigatória ausente: {$claim}");
            }
        }

        if (($decoded->iss ?? null) !== config('jwt.expected_iss', 'wiseflow-auth')) {
            throw new JwtInvalidTokenException('Emissor inválido');
        }

        if (! $this->audienceMatches($decoded->aud, (string) config('jwt.expected_aud', 'wiseflow'))) {
            throw new JwtInvalidTokenException('Audiência inválida');
        }

        if (! is_numeric($decoded->exp) || ! is_numeric($decoded->nbf) || ! is_numeric($decoded->iat)) {
            throw new JwtInvalidTokenException('Claims temporais inválidas');
        }

        if ((int) $decoded->nbf > time()) {
            throw new JwtUnauthorizedException('Token ainda não válido');
        }

        if ((int) $decoded->iat > time()) {
            throw new JwtInvalidTokenException('Token emitido no futuro');
        }

        if (! is_string($decoded->jti) || trim($decoded->jti) === '') {
            throw new JwtInvalidTokenException('JTI inválido');
        }

        if (! is_object($decoded->user) && ! is_array($decoded->user)) {
            throw new JwtInvalidTokenException('Claim user inválida');
        }

        if (! $this->tenantHasId($decoded->tenant)) {
            throw new JwtInvalidTokenException('Tenant inválido');
        }

        if (! is_array($decoded->roles) || ! is_array($decoded->permissions)) {
            throw new JwtInvalidTokenException('RBAC inválido');
        }
    }

    protected function audienceMatches(mixed $audience, string $expected): bool
    {
        if (is_string($audience)) {
            return $audience === $expected;
        }

        if (is_array($audience)) {
            return in_array($expected, $audience, true);
        }

        return false;
    }

    protected function tenantHasId(mixed $tenant): bool
    {
        if (is_object($tenant)) {
            return isset($tenant->id) && (string) $tenant->id !== '';
        }

        if (is_array($tenant)) {
            return isset($tenant['id']) && (string) $tenant['id'] !== '';
        }

        return false;
    }

    protected function looksLikePem(string $key): bool
    {
        return str_contains($key, '-----BEGIN PUBLIC KEY-----')
            || str_contains($key, '-----BEGIN RSA PUBLIC KEY-----')
            || str_contains($key, '-----BEGIN CERTIFICATE-----');
    }

    protected function looksLikePath(string $key): bool
    {
        return str_contains($key, DIRECTORY_SEPARATOR)
            || str_contains($key, '/')
            || str_contains($key, '\\');
    }

    protected function readPublicKeyFile(string $path): string
    {
        $realPath = realpath($path);

        if ($realPath === false || ! is_readable($realPath)) {
            throw new JwtConfigurationException('Ficheiro de chave pública JWT não encontrado');
        }

        if (! $this->isAllowedKeyPath($realPath)) {
            throw new JwtConfigurationException('Caminho da chave pública JWT não permitido');
        }

        $contents = file_get_contents($realPath);

        if ($contents === false || ! $this->looksLikePem($contents)) {
            throw new JwtConfigurationException('Conteúdo da chave pública JWT inválido');
        }

        return $contents;
    }

    protected function isAllowedKeyPath(string $path): bool
    {
        $allowedPaths = (array) config('jwt.public_key_allowed_paths', []);

        foreach ($allowedPaths as $allowedPath) {
            if (! is_string($allowedPath) || trim($allowedPath) === '') {
                continue;
            }

            $realAllowedPath = realpath($allowedPath);

            if ($realAllowedPath !== false && str_starts_with($path, $realAllowedPath.DIRECTORY_SEPARATOR)) {
                return true;
            }

            if ($realAllowedPath !== false && $path === $realAllowedPath) {
                return true;
            }
        }

        return false;
    }
}

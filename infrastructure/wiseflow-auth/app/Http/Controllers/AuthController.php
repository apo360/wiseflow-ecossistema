<?php

// app/Http/Controllers/AuthController.php
namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RefreshTokenRequest;
use App\Services\AuthService;
use App\Services\CoreDatabaseService;
use App\Services\RefreshTokenService;
use App\Services\TokenRevocationService;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
        protected RefreshTokenService $refreshTokenService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->attemptLogin(
                $request->email,
                $request->password,
                $request->input('tenant_slug')
            );
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }

        if (!$result) {
            return response()->json(['error' => 'Credenciais inválidas'], 401);
        }

        $refreshToken = $this->refreshTokenService->create(
            $result['user']->id,
            $result['tenant_id']
        );

        return response()->json([
            'access_token' => $result['access_token'],
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }

    public function me(): JsonResponse
    {
        return response()->json(auth('api')->user());
    }

    public function logout()
    {
        $payload = auth('api')->payload();
        $jti = $payload->get('jti');
        $exp = $payload->get('exp'); // timestamp de expiração
        $ttl = $exp - time();        // segundos restantes
        $userId = $payload->get('user')['id'];
        $tenantId = $payload->get('tenant')['id'];

        if ($jti && $ttl > 0) {
            app(TokenRevocationService::class)->revoke($jti, $ttl);
        }

        // Invalida refresh tokens...
        auth('api')->logout();

        return response()->json(['message' => 'Logout com sucesso']);
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $refreshToken = $this->refreshTokenService->validateAndRetrieve($request->refresh_token);

        if (!$refreshToken) {
            return response()->json(['error' => 'Refresh token inválido ou expirado'], 401);
        }

        $user = \App\Models\User::find($refreshToken->user_id);

        // Rebuscar RBAC actualizado do Core
        try {
            $rbac = app(CoreDatabaseService::class)->getUserRbac($user->id, $refreshToken->tenant_id);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Não foi possível obter permissões actualizadas'], 500);
        }

        $customClaims = [
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
            ],
            'tenant' => [
                'id' => $refreshToken->tenant_id,
                'slug' => $rbac['tenant_slug'] ?? '',
            ],
            'roles' => $rbac['roles'] ?? [],
            'permissions' => $rbac['permissions'] ?? [],
        ];

        // Revogar token de acesso atual (se existir)
        if ($token = auth('api')->getToken()) {
            $payload = auth('api')->payload();
            $jti = $payload->get('jti');
            $exp = $payload->get('exp');
            $ttl = $exp - time();
            if ($jti && $ttl > 0) {
                app(TokenRevocationService::class)->revoke($jti, $ttl);
            }
        }

        $newAccessToken = JWTAuth::claims($customClaims)->fromUser($user);

        // Rotação do refresh token
        $newRefreshToken = $this->refreshTokenService->create($user->id, $refreshToken->tenant_id);
        $this->refreshTokenService->revoke($refreshToken);

        return response()->json([
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }
}
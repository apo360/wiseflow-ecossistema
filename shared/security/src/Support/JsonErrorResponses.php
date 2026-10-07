<?php

namespace Wiseflow\JwtAuth\Support;

use Illuminate\Http\JsonResponse;

trait JsonErrorResponses
{
    protected function unauthorized(string $message = 'Não autenticado'): JsonResponse
    {
        return $this->errorResponse('unauthorized', $message, 401);
    }

    protected function forbidden(string $message = 'Acesso negado'): JsonResponse
    {
        return $this->errorResponse('forbidden', $message, 403);
    }

    protected function errorResponse(string $error, string $message, int $status): JsonResponse
    {
        return response()->json([
            'error' => $error,
            'message' => $message,
        ], $status);
    }
}

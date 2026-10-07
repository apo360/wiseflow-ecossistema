<?php

namespace App\Http\Controllers;

use App\Services\TokenRevocationService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Logout do utilizador, revogando o token actual.
     * Nota: a revogação em massa de tokens de um utilizador num tenant é mais complexa e pode ser implementada no AuthService ou TokenRevocationService.
     */
    public function logout(Request $request)
    {
        $user = auth('api')->user();
        if ($user) {
            auth('api')->logout();
            // Opcional: revogar o token actual adicionando o jti à blacklist
            $payload = auth('api')->payload();
            $jti = $payload->get('jti');
            $exp = $payload->get('exp');
            $ttl = $exp - time();
        }
        // Retornar resposta de logout bem-sucedido
        return response()->json(['message' => 'Logout successful']);
    }

    /**
     * Alterar password do utilizador.
     */
    public function changePassword(Request $request)
    {
        // validar e alterar senha do utilizador

        // Revogar tokens atuais do utilizador (opcional, dependendo da política de segurança)
        // Pode-se revogar apenas o token actual ou todos os tokens do utilizador num tenant
        // Para revogar apenas o token actual, basta chamar auth('api')->logout() que revoga o token actual. Para revogar todos os tokens, pode-se usar o TokenRevocation
        $payload = auth('api')->payload();
        $userId = $payload->get('user')['id'];
        $tenantId = $payload->get('tenant')['id'];

        app(TokenRevocationService::class)->revokeAllForUser($userId, $tenantId);

        return response()->json(['message' => 'Password changed successfully']);
    }
}

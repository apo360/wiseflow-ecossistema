<?php

if (! function_exists('auth_user')) {
    /**
     * Retorna os dados do utilizador autenticado extraídos do JWT.
     */
    function auth_user(): ?object
    {
        $user = request()->attributes->get('auth_user');

        return $user ? (object) $user : null;
    }
}

if (! function_exists('auth_tenant')) {
    /**
     * Retorna os dados do tenant actual extraídos do JWT.
     */
    function auth_tenant(): ?object
    {
        $tenant = request()->attributes->get('auth_tenant');

        return $tenant ? (object) $tenant : null;
    }
}

if (! function_exists('auth_roles')) {
    /**
     * Retorna a lista de roles do utilizador no contexto actual.
     */
    function auth_roles(): array
    {
        return request()->attributes->get('auth_roles', []);
    }
}

if (! function_exists('auth_permissions')) {
    /**
     * Retorna a lista de permissões do utilizador no contexto actual.
     */
    function auth_permissions(): array
    {
        return request()->attributes->get('auth_permissions', []);
    }
}

if (! function_exists('auth_has_permission')) {
    /**
     * Verifica se o utilizador possui uma determinada permissão.
     */
    function auth_has_permission(string $permission): bool
    {
        return in_array($permission, auth_permissions(), true);
    }
}

if (! function_exists('auth_has_role')) {
    /**
     * Verifica se o utilizador possui um determinado role.
     */
    function auth_has_role(string $role): bool
    {
        return in_array($role, auth_roles(), true);
    }
}

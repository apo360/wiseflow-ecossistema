<?php

// app/Services/CoreDatabaseService.php
namespace App\Services;

use App\Models\Core\TenantUser;
use App\Models\Core\Permission;
use Illuminate\Support\Facades\DB;

class CoreDatabaseService
{
    /**
     * Obter todos os tenants aos quais o utilizador pertence.
     */
    public function getUserTenants(int $userId): array
    {
        $tenantUsers = TenantUser::where('user_id', $userId)
            ->with('tenant:id,slug,name')
            ->get();

        return $tenantUsers->map(function ($tu) {
            return [
                'id' => $tu->tenant->id,
                'slug' => $tu->tenant->slug,
                'name' => $tu->tenant->name,
            ];
        })->all();
    }

    /**
     * Obter roles e permissões do utilizador num tenant específico.
     */
    public function getUserRbac(int $userId, int $tenantId): array
    {
        $tenantUser = TenantUser::where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->with('tenant', 'role')
            ->first();

        if (!$tenantUser) {
            throw new \Exception('Utilizador não associado a este tenant.');
        }

        $role = $tenantUser->role;
        $roles = $role ? [$role->slug] : [];

        // Permissões diretas do utilizador (tabela pivot permission_user)
        $directPermissions = DB::connection('core')
            ->table('permission_user')
            ->where('user_id', $userId)
            ->pluck('permission_id')
            ->toArray();

        // Permissões herdadas da role
        $rolePermissions = $role
            ? $role->permissions()->pluck('permissions.id')->toArray()
            : [];

        $permissionIds = array_unique(array_merge($directPermissions, $rolePermissions));

        $permissionSlugs = Permission::whereIn('id', $permissionIds)->pluck('slug')->toArray();

        return [
            'tenant_slug' => $tenantUser->tenant->slug,
            'roles' => $roles,
            'permissions' => $permissionSlugs,
        ];
    }

    /**
     * Obter os modulos e funcionalidades disponíveis para um tenant.
     */
    public function getTenantModules(int $tenantId): array
    {
        return DB::connection('core')
            ->table('tenant_modules')
            ->join('modules', 'modules.id', '=', 'tenant_modules.module_id')
            ->where('tenant_modules.tenant_id', $tenantId)
            ->where('tenant_modules.is_enabled', true)
            ->where('modules.is_active', true)
            ->select('modules.id', 'modules.name', 'modules.slug', 'tenant_modules.settings')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'slug' => $item->slug,
                    'url' => $this->buildServiceUrl($item->slug),
                    'settings' => json_decode($item->settings, true),
                ];
            })
            ->all();
    }

    protected function buildServiceUrl(string $moduleSlug): string
    {
        $urls = [
            'kixikila' => 'http://kixikila.wiseflow.local',
            'finance'  => 'http://finance.wiseflow.local',
            'lite'     => 'http://particulares.wiseflow.local',
        ];
        return $urls[$moduleSlug] ?? '#';
    }

}
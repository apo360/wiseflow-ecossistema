<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tenant extends Model
{
    /**
     * Conexão com a base de dados wiseflow_core.
     */
    protected $connection = 'core';

    /**
     * Tabela associada.
     */
    protected $table = 'tenants';

    /**
     * Atributos que podem ser atribuídos em massa.
     */
    protected $fillable = [
        'slug',
        'name',
        'status',
    ];

    /**
     * Atributos que devem ser convertidos para tipos nativos.
     */
    protected $casts = [
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relacionamentos
    |--------------------------------------------------------------------------
    */

    /**
     * Utilizadores associados a este tenant.
     */
    public function tenantUsers(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    /**
     * Módulos habilitados para este tenant.
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'tenant_modules')
                    ->withPivot('is_enabled', 'settings')
                    ->withTimestamps();
    }

    /**
     * Roles definidas para este tenant (inclui roles globais? Normalmente não).
     */
    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    /**
     * Configurações específicas do tenant.
     */
    public function settings(): HasMany
    {
        return $this->hasMany(Setting::class);
    }

    /**
     * Logs de auditoria do tenant.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}

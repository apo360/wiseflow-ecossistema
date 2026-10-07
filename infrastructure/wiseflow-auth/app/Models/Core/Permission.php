<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $connection = 'core';
    protected $table = 'permissions';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'module',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relacionamentos
    |--------------------------------------------------------------------------
    */

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission')
                    ->withTimestamps();
    }

    /**
     * Utilizadores com esta permissão diretamente atribuída.
     * Nota: user_id refere-se a utilizadores da BD auth.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'permission_user', 'permission_id', 'user_id')
                    ->withPivot('tenant_id')
                    ->withTimestamps();
    }
}
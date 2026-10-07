<?php

namespace Wiseflow\JwtAuth;

use Illuminate\Support\ServiceProvider;
use Wiseflow\JwtAuth\Middleware\AuditMiddleware;
use Wiseflow\JwtAuth\Middleware\CheckPermission;
use Wiseflow\JwtAuth\Middleware\CheckRole;
use Wiseflow\JwtAuth\Middleware\CheckTenant;
use Wiseflow\JwtAuth\Middleware\JwtAuthenticate;

class JwtAuthServiceProvider extends ServiceProvider
{
    /**
     * Regista serviços no contentor.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/jwt.php', 'jwt'
        );
    }

    /**
     * Executa inicializações após registo de todos os providers.
     */
    public function boot(): void
    {
        // Publicar ficheiro de configuração
        $this->publishes([
            __DIR__.'/../config/jwt.php' => config_path('jwt.php'),
        ], 'wiseflow-jwt-config');

        // Registar middleware no router
        $router = $this->app['router'];
        $router->aliasMiddleware('jwt.auth', JwtAuthenticate::class);
        $router->aliasMiddleware('jwt.permission', CheckPermission::class);
        $router->aliasMiddleware('jwt.role', CheckRole::class);
        $router->aliasMiddleware('jwt.tenant', CheckTenant::class);
        $router->aliasMiddleware('jwt.audit', AuditMiddleware::class);
    }
}

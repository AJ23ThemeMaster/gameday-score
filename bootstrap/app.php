<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // DISI-15: alias para los middlewares de permisologia
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            // DISI-81: alias para acciones permitidas a admin o gestor
            'admin_or_gestor' => \App\Http\Middleware\EnsureAdminOrGestor::class,
            // DISI-delegado: admin, gestor (con team) o delegado (con team + categoria).
            // Usado para endpoints donde las tres figuras pueden leer/editar
            // atletas pero con scope distinto segun el rol.
            'admin_or_gestor_or_delegado' => \App\Http\Middleware\EnsureAdminOrGestorOrDelegado::class,
            // DISI-16b: alias para el challenge de 2FA tras login
            '2fa.challenge' => \App\Http\Middleware\EnsureTwoFactorChallenged::class,
        ]);

        // DISI-X: auto-leave de impersonacion por timeout. Se ejecuta
        // en TODAS las requests web (no hace nada si no hay
        // impersonacion activa). Lee directo de la sesion, asi que
        // no depende del orden respecto a auth.
        $middleware->appendToGroup('web', \App\Http\Middleware\EnforceImpersonationTimeout::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

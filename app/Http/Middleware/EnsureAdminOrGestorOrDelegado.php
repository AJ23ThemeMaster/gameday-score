<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * DISI-delegado: permite el paso a:
 *  - admin (cualquiera)
 *  - gestor con team asignado
 *  - delegado con team Y categoria asignados
 *
 * El chequeo fino de scope (atleta pertenece al equipo/categoria del
 * gestor o delegado) se hace dentro de cada controlador con
 * User::isGestorOwning() / User::isDelegadoOf().
 *
 * Nota: un admin sin equipo sigue contando como admin (tiene acceso
 * global). Para los atletas sin team_id + category_id, isDelegado()
 * retorna false y se bloquean (mejor que dejar un delegado sin scope).
 */
class EnsureAdminOrGestorOrDelegado
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(403, 'Necesitas iniciar sesión para acceder a esta sección.');
        }

        $isAdmin = $user->isAdmin();
        $isGestor = $user->isGestor();
        $isDelegado = $user->isDelegado();

        if (! $isAdmin && ! $isGestor && ! $isDelegado) {
            abort(403, 'Solo el administrador, un gestor con equipo o un delegado con equipo y categoria pueden acceder a esta sección.');
        }

        return $next($request);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * DISI-81: middleware que permite el paso solo a usuarios con rol 'admin'
 * o 'gestor'. El chequeo de scope (gestor solo puede actuar sobre su
 * equipo asociado) lo hace cada controlador con User::isGestorOwning().
 */
class EnsureAdminOrGestor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(403, 'Necesitas iniciar sesión para acceder a esta sección.');
        }

        $isAdmin = $user->hasRole('admin');
        $isGestor = $user->hasRole('gestor') && $user->team_id !== null;

        if (! $isAdmin && ! $isGestor) {
            abort(403, 'Solo el administrador o un gestor pueden acceder a esta sección.');
        }

        return $next($request);
    }
}
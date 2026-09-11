<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * DISI-15: middleware que permite el paso solo a usuarios con rol 'admin'.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->hasRole('admin')) {
            abort(403, 'Solo el administrador puede acceder a esta sección.');
        }

        return $next($request);
    }
}

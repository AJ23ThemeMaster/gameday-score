<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Lab404\Impersonate\Services\ImpersonateManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * DISI-X: fuerza auto-leave de una sesion de impersonacion cuando
 * ha pasado mas tiempo que `max_impersonation_minutes` (default 10)
 * desde que empezo.
 *
 * Como funciona:
 *  - LogImpersonation::handleTake graba `impersonation_started_at`
 *    en la sesion al disparar TakeImpersonation.
 *  - En cada request, este middleware lee ese timestamp, lo compara
 *    con el max configurado y, si expiro, llama al manager del paquete
 *    para salir limpiamente + redirige con un flash de error.
 *
 *  - LogImpersonation::handleLeave limpia el timestamp.
 *  - Si el timestamp no existe (take viejo antes de este commit o
 *    tras restart de sesion), no hacemos nada -> permite cerrar
 *    manualmente sin forzar.
 *
 * Nota de seguridad: si un atacante roba la sesion admin mientras
 * esta impersonando, como mucho tendra 10 minutos de "libertad"
 * antes de que el middleware lo saque automaticamente.
 */
class EnforceImpersonationTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        // 0 = desactivado. Util en desarrollo o tests.
        $maxMinutes = (int) config('laravel-impersonate.max_impersonation_minutes', 10);
        if ($maxMinutes <= 0) {
            return $next($request);
        }

        // Lee directo de la sesion (no necesita auth()->user() cargado,
        // asi puede correr en cualquier punto del stack).
        $sessionKey = config('laravel-impersonate.session_key', 'impersonated_by');
        $startedKey = config('laravel-impersonate.session_started_key', 'impersonation_started_at');

        if (! session()->has($sessionKey)) {
            return $next($request); // no impersonando
        }

        $startedAt = session($startedKey);
        if (! $startedAt) {
            return $next($request); // sin timestamp, no podemos calcular; dejamos pasar
        }

        try {
            $started = Carbon::parse($startedAt);
        } catch (\Throwable) {
            return $next($request);
        }

        $elapsed = $started->diffInMinutes(now(), false); // signed; positivo si expiro
        if ($elapsed < $maxMinutes) {
            return $next($request);
        }

        // Sesion expirada: forzar leave + redirigir.
        try {
            app(ImpersonateManager::class)->leave();
        } catch (\Throwable) {
            // Si leave() falla (ej: usuario original fue eliminado), al
            // menos limpiamos la sesion para que el siguiente request
            // no se quede pegado en impersonacion.
        }
        session()->forget($sessionKey);
        session()->forget($startedKey);
        // Tambien limpiamos las otras claves que el paquete usa.
        session()->forget(config('laravel-impersonate.session_guard', 'impersonator_guard'));
        session()->forget(config('laravel-impersonate.session_guard_using', 'impersonator_guard_using'));

        return redirect('/dashboard')->with('error', "Tu sesión de impersonación expiró después de {$maxMinutes} minutos.");
    }
}
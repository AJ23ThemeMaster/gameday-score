<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * DISI-16b: Middleware que fuerza al usuario a pasar el challenge 2FA
 * (codigo TOTP de 6 digitos) en cada login si lo tiene habilitado.
 *
 * - Si el usuario no esta autenticado, deja pasar (otro middleware lo maneja).
 * - Si el usuario no tiene 2FA habilitado, deja pasar.
 * - Si el usuario tiene 2FA Y ya paso el challenge en esta sesion
 *   (session('two_factor_passed_at') reciente), deja pasar.
 * - Si tiene 2FA pero NO ha pasado el challenge, redirige a /two-factor-challenge.
 */
class EnsureTwoFactorChallenged
{
    /**
     * Tiempo maximo de validez del challenge dentro de la sesion.
     * Por defecto 12 horas (alineado con Laravel Fortify).
     */
    public const CHALLENGE_LIFETIME_MINUTES = 12 * 60;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Si el usuario no tiene 2FA habilitado, no aplica el challenge.
        if (! method_exists($user, 'hasTwoFactorEnabled') || ! $user->hasTwoFactorEnabled()) {
            return $next($request);
        }

        $passedAt = $request->session()->get('two_factor_passed_at');
        if ($passedAt !== null) {
            try {
                $carbon = \Illuminate\Support\Carbon::parse((string) $passedAt);
                $expires = $carbon->copy()->addMinutes(self::CHALLENGE_LIFETIME_MINUTES);
                if ($expires->isFuture()) {
                    return $next($request);
                }
            } catch (\Throwable $e) {
                // ignore parse errors, fall through to redirect
            }
        }

        // Guardar la URL a la que intentaba acceder para redirigir despues del challenge.
        if (! $request->session()->has('two_factor_intended_url')) {
            $request->session()->put('two_factor_intended_url', $request->fullUrl());
        }

        return redirect()->route('two-factor.challenge');
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmTwoFactorRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * DISI-16b: Challenge 2FA al iniciar sesion.
 *
 * - show(): muestra el formulario para ingresar el codigo TOTP de 6 digitos.
 *           Auto-submit via JS al completar 6 digitos (sin boton).
 * - verify(): valida el codigo, marca la sesion como "pasada" y redirige a la URL intended.
 */
class TwoFactorChallengeController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user || ! $user->hasTwoFactorEnabled()) {
            return redirect()->intended(route('dashboard'));
        }

        return view('auth.two-factor-challenge');
    }

    public function verify(ConfirmTwoFactorRequest $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user || ! $user->hasTwoFactorEnabled()) {
            return redirect()->intended(route('dashboard'));
        }

        $code = $request->string('code')->toString();

        if (! $user->verifyTwoFactorCode($code)) {
            return back()
                ->withErrors(['code' => 'El codigo ingresado no es valido. Verifica la hora de tu dispositivo e intenta de nuevo.'])
                ->withInput();
        }

        // Challenge OK: marcar sesion.
        // Guardamos como string (toDateTimeString) en lugar de Carbon para
        // evitar problemas de serializacion al recuperar el flag en requests
        // subsecuentes.
        $request->session()->put('two_factor_passed_at', now()->toDateTimeString());

        $intended = $request->session()->pull('two_factor_intended_url') ?: route('dashboard');

        return redirect()->to($intended);
    }

    /**
     * Cancelar challenge y cerrar sesion.
     */
    public function cancel(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

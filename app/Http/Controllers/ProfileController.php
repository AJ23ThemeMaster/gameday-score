<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\ConfirmTwoFactorRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PragmaRX\Google2FAQRCode\Google2FA;

class ProfileController extends Controller
{
    /**
     * Mostrar el formulario de "Mi perfil".
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Actualizar datos personales (nombre, correo, avatar).
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($request->boolean('remove_avatar') && $user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->avatar_path = null;
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Actualizar la contraseña del usuario actual.
     */
    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->string('password')->toString(),
        ]);

        return Redirect::route('profile.edit')->with('status', 'password-updated');
    }

    /**
     * Eliminar el avatar actual (helper desde el form).
     */
    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->avatar_path = null;
            $user->save();
        }

        return Redirect::route('profile.edit')->with('status', 'avatar-removed');
    }

    /**
     * Eliminar la cuenta del usuario.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        // Limpia avatar al eliminar la cuenta
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    // ---------------------------------------------------------------------
    // DISI-16: Autenticacion en 2 pasos (2FA) con TOTP / Google Authenticator
    // ---------------------------------------------------------------------

    /**
     * Iniciar el flujo de activacion de 2FA.
     * Genera el secreto, lo persiste y devuelve la vista con QR + secret.
     */
    public function enableTwoFactor(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        // Si ya esta confirmado, no regenerar (usar disable primero).
        if ($user->hasTwoFactorEnabled()) {
            return Redirect::route('profile.edit')->with('error', 'La autenticacion en 2 pasos ya esta activa.');
        }

        if (empty($user->two_factor_secret)) {
            $user->generateTwoFactorSecret();
        }

        $google2fa = new Google2FA();
        $secret = (string) $user->two_factor_secret;
        $appName = config('app.name', 'Gameday Score');
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            $appName,
            $user->email,
            $secret,
        );
        // QR inline (SVG por defecto si Imagick no esta disponible)
        $qrCodeInline = $google2fa->getQRCodeInline(
            $appName,
            $user->email,
            $secret,
            300,
        );

        return view('profile.two-factor.enable', [
            'secret' => $secret,
            'qrCodeUrl' => $qrCodeUrl,
            'qrCodeInline' => $qrCodeInline,
            'user' => $user,
        ]);
    }

    /**
     * Confirmar el 2FA validando un codigo TOTP del usuario.
     */
    public function confirmTwoFactor(ConfirmTwoFactorRequest $request): View|RedirectResponse
    {
        $user = $request->user();

        if (empty($user->two_factor_secret)) {
            return Redirect::route('profile.edit')
                ->with('error', 'No hay un secreto 2FA pendiente. Inicia la activacion primero.');
        }

        if (! $user->verifyTwoFactorCode($request->string('code')->toString())) {
            return Redirect::route('profile.two-factor.enable')
                ->withErrors(['code' => 'El codigo ingresado no es valido. Verifica la hora de tu dispositivo e intenta de nuevo.']);
        }

        $recoveryCodes = $user->enableTwoFactor();

        return view('profile.two-factor.recovery-codes', [
            'recoveryCodes' => $recoveryCodes,
            'user' => $user,
        ])->with('status', 'two-factor-enabled');
    }

    /**
     * Desactivar 2FA.
     */
    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return Redirect::route('profile.edit')
                ->with('error', 'La autenticacion en 2 pasos no esta activa.');
        }

        $request->validateWithBag('disableTwoFactor', [
            'password' => ['required', 'current_password'],
        ]);

        $user->disableTwoFactor();

        return Redirect::route('profile.edit')->with('status', 'two-factor-disabled');
    }

    /**
     * Regenerar codigos de recuperacion.
     */
    public function regenerateRecoveryCodes(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return Redirect::route('profile.edit')
                ->with('error', 'La autenticacion en 2 pasos no esta activa.');
        }

        $request->validateWithBag('regenerateCodes', [
            'password' => ['required', 'current_password'],
        ]);

        $recoveryCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $recoveryCodes[] = strtoupper(bin2hex(random_bytes(5)));
        }
        $user->two_factor_recovery_codes = json_encode($recoveryCodes);
        $user->save();

        return view('profile.two-factor.recovery-codes', [
            'recoveryCodes' => $recoveryCodes,
            'user' => $user,
        ])->with('status', 'recovery-codes-regenerated');
    }
}

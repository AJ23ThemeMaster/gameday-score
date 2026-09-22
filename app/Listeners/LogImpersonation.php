<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Lab404\Impersonate\Events\TakeImpersonation;

/**
 * Auditoria de impersonaciones (lab404/laravel-impersonate).
 *
 * Cada TakeImpersonation / LeaveImpersonation queda registrada en el
 * canal de log estandar (storage/logs/laravel-*.log) con:
 *  - impersonator_id y target_id
 *  - IP del cliente
 *  - timestamp
 *
 * Es importante sobre todo cuando se permite que admins se impersonen
 * entre si (decision del usuario), porque de lo contrario el audit
 * log del sistema solo veria "el admin B hizo X" sin saber que el
 * origen fue el admin A.
 */
class LogImpersonation
{
    public function handleTake(TakeImpersonation $event): void
    {
        $impersonator = $event->impersonator;
        $target = $event->impersonated;

        // DISI-X: marca de inicio para que el middleware
        // EnforceImpersonationTimeout sepa cuando expira la sesion.
        $startedKey = config('laravel-impersonate.session_started_key', 'impersonation_started_at');
        session([$startedKey => now()->toDateTimeString()]);

        Log::info('Impersonation started', [
            'impersonator_id' => $impersonator?->getAuthIdentifier(),
            'impersonator_email' => $impersonator?->email,
            'target_id' => $target?->getAuthIdentifier(),
            'target_email' => $target?->email,
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    public function handleLeave(LeaveImpersonation $event): void
    {
        $manager = app(\Lab404\Impersonate\Services\ImpersonateManager::class);
        $startedKey = config('laravel-impersonate.session_started_key', 'impersonation_started_at');
        session()->forget($startedKey);

        Log::info('Impersonation ended', [
            'impersonator_id' => $manager->getImpersonatorId(),
            'target_id' => $event->impersonated?->getAuthIdentifier(),
            'target_email' => $event->impersonated?->email,
            'ip' => request()?->ip(),
        ]);
    }
}
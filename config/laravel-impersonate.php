<?php

return [

    /**
     * The session key used to store the original user id.
     */
    'session_key' => 'impersonated_by',

    /**
     * The session key used to stored the original user guard.
     */
    'session_guard' => 'impersonator_guard',

    /**
     * The session key used to stored what guard is impersonator using.
     */
    'session_guard_using' => 'impersonator_guard_using',

    /**
     * The default impersonator guard used.
     */
    'default_impersonator_guard' => 'web',

    /**
     * DISI-X: timestamp (ISO 8601) en que se inicio la impersonacion
     * actual. Lo escribe el listener LogImpersonation al disparar
     * TakeImpersonation y lo limpia al disparar LeaveImpersonation.
     * Lo lee el middleware EnforceImpersonationTimeout en cada request.
     */
    'session_started_key' => 'impersonation_started_at',

    /**
     * DISI-X: minutos maximos de una sesion de impersonacion.
     * Pasado ese tiempo, el middleware EnforceImpersonationTimeout
     * fuerza leave() y redirige con un flash de error.
     * Default: 10 minutos. 0 desactiva el limite (no recomendado).
     */
    'max_impersonation_minutes' => env('IMPERSONATE_MAX_MINUTES', 10),

    /**
     * The URI to redirect after taking an impersonation.
     *
     * Only used in the built-in controller.
     * * Use 'back' to redirect to the previous page
     */
    'take_redirect_to' => '/dashboard',

    /**
     * The URI to redirect after leaving an impersonation.
     *
     * Only used in the built-in controller.
     * Use 'back' to redirect to the previous page
     */
    'leave_redirect_to' => '/dashboard',

];

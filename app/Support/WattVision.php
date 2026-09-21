<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Helper centralizado para el sistema de diseno WattVision (feature/style/wattvision).
 *
 * El redise~o se aplica a TODO el sistema EXCEPTO a las vistas de control y
 * presentacion del juego en vivo (scorekeeper + espectador), porque esas vistas
 * tienen layouts y estilos propios que ya estan consolidados y probados.
 *
 * Cualquier layout / partial / componente que necesite condicionar su apariencia
 * al flag "estoy en una vista excluida?" debe usar self::isExcluded().
 *
 * Mantener EXCLUDED_ROUTES sincronizado con el DESIGN.md (seccion de exclusiones)
 * y con los comentarios en resources/views/layouts/app.blade.php.
 */
final class WattVision
{
    /**
     * Rutas NO redise~adas. Aqui viven:
     * - games.scoreboard    : panel de control del anotador (scorekeeper).
     * - games.box-score     : estadisticas post-juego (control).
     * - games.live          : control de juego en vivo (anotador).
     * - public.games.show   : marcador publico para espectadores (mismo handler
     *                          que live.public, mismo layout).
     * - games.live.public   : alias corto del marcador publico
     *                          (/game/live/{token} vs /juego/publico/{token}).
     *
     * Wildcards de Laravel Route::is() NO se usan: las exclusiones son exactas
     * para no comerse sin querer rutas nuevas que hereden el prefijo.
     *
     * @var list<string>
     */
    public const EXCLUDED_ROUTES = [
        'games.scoreboard',
        'games.box-score',
        'games.live',
        'public.games.show',
        'games.live.public',
    ];

    /**
     * Devuelve true si la ruta actual esta en la lista de exclusion y, por
     * tanto, NO debe recibir el redise~o WattVision (queda con su estilo
     * original / light).
     *
     * Acepta el nombre de ruta opcional para que se pueda testear o usar
     * desde Blade con el nombre precalculado.
     */
    public static function isExcluded(?string $routeName = null): bool
    {
        $name = $routeName ?? request()->route()?->getName();

        return $name !== null && in_array($name, self::EXCLUDED_ROUTES, true);
    }
}
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Game;
use App\Models\User;

/**
 * DISI-15: Politica de acceso a juegos.
 *
 * Reglas:
 * - admin: gestiona todo (CRUD + anotar en cualquier juego).
 * - delegado con scope automatico: rol 'delegado' con team_id + category_id
 *   no nulos. Su scope es (su equipo, su categoria) y puede VER + ANOTAR +
 *   GESTIONAR ROSTER de cualquier juego donde
 *   (home_team_id == team_id OR away_team_id == team_id) Y
 *   category_id == category_id. No requiere 'anotador' como segundo rol ni
 *   asignacion manual game_scorekeeper.
 * - anotador (con o sin scope automatico):
 *      (a) anotador + delegado (con scope): cae en la rama delegado de arriba.
 *      (b) anotador puro: solo puede VER + ANOTAR los juegos donde esta
 *          asignado (game_scorekeeper.scorekeeper_id apunta a un scorekeeper
 *          cuyo user_id == $user->id).
 * - resto: solo lectura si son owner del juego.
 *
 * Piloto: la union (a)+(c) permite que un delegado del equipo gestione los
 * juegos de su scope (roster, scoreboard, anotar jugadas, abrir live, etc.)
 * ya sea con solo el rol 'delegado' (mas comodo) o sumandole 'anotador'
 * (caso anotador+delegado del piloto previo). El 'isDelegado()' exige
 * AMBOS FK (team + cat) no nulos, asi que un delegado "flotante" sin scope
 * nunca habilita las ramas automaticas por accidente.
 */
class GamePolicy
{
    /**
     * Ver un juego (scoreboard, detalle, roster).
     */
    public function view(User $user, Game $game): bool
    {
        return $this->userCanAccessGame($user, $game, requireOwner: true);
    }

    /**
     * Actualizar/Crear juegos (CRUD).
     */
    public function create(User $user): bool
    {
        // Admin: crea donde quiera.
        // Delegado con scope (team_id + category_id no nulos): puede crear
        // juegos para su (equipo, categoria). El controller filtra los
        // dropdowns y valida que home_team_id/away_team_id/category_id
        // caigan dentro del scope; si no, devuelve 403 explicito.
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('delegado') && $this->userHasDelegateScope($user);
    }

    public function update(User $user, Game $game): bool
    {
        // Mismas reglas que view: anotador puede 'editar' (cambiar roster,
        // reordenar lineup, sustituir) en cualquier juego al que tenga acceso.
        return $this->userCanAccessGame($user, $game, requireOwner: true);
    }

    public function delete(User $user, Game $game): bool
    {
        // DISI-piloto: por seguridad, eliminar juegos queda restringido a
        // admin. Un delegado con scope NO debe borrar juegos (riesgo de
        // borrado accidental). Si en el futuro se quiere habilitar,
        // considerar soft-delete + workflow con audit trail.
        return $user->hasRole('admin');
    }

    /**
     * Anotar jugadas en vivo (pitch, end-inning, end-game, substitute, runner action).
     *
     * Admin: cualquier juego.
     * Delegado con scope automatico: juegos de (su equipo, su categoria).
     * Anotador con scope automatico: igual que delegado.
     * Anotador puro: solo donde esta asignado via game_scorekeeper.
     */
    public function score(User $user, Game $game): bool
    {
        return $this->userCanAccessGame($user, $game, requireOwner: false);
    }

    /**
     * Chequeo unificado de acceso a juego.
     *
     * @param bool $requireOwner Si true, un usuario sin scope ni rol anotador
     *                           podra acceder al juego SOLO si es owner
     *                           (compat con el comportamiento anterior que
     *                           permitia 'owner del juego'). Para 'score'
     *                           pasamos false porque los anotadores de scope
     *                           ajeno al juego owner deben poder anotar igual.
     */
    protected function userCanAccessGame(User $user, Game $game, bool $requireOwner): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        // (a) y (c): el delegadado con scope automatico es due\u00f1o logico de los
        // juegos de su (equipo, categoria). Esto cubre anotador+delegado y
        // delegado-puro.
        if ($user->hasRole('delegado') && $this->userInDelegateScope($user, $game)) {
            return true;
        }

        if ($user->hasRole('anotador')) {
            // (b) anotador puro: asignado via game_scorekeeper
            if ($this->userIsAssignedToGame($user, $game)) {
                return true;
            }
        }

        // Owner del juego puede ver / editar si requireOwner
        if ($requireOwner && $user->id === $game->user_id) {
            return true;
        }

        return false;
    }

    /**
     * Helper: el usuario es delegado con scope (team_id + category_id) y el
     * juego cae dentro de ese scope (su equipo, su categoria).
     */
    protected function userInDelegateScope(User $user, Game $game): bool
    {
        if (! $this->userHasDelegateScope($user)) {
            return false;
        }
        $inTeamScope = (int) $game->home_team_id === (int) $user->team_id
            || (int) $game->away_team_id === (int) $user->team_id;

        return $inTeamScope && (int) $game->category_id === (int) $user->category_id;
    }

    /**
     * Helper: el usuario tiene el rol 'delegado' con team_id + category_id
     * no nulos (scope utilizable, sin importar para que juego).
     */
    protected function userHasDelegateScope(User $user): bool
    {
        return $user->hasRole('delegado')
            && $user->team_id !== null
            && $user->category_id !== null;
    }

    /**
     * Helper: el usuario esta asignado como anotador (scorekeeper) en este juego.
     */
    protected function userIsAssignedToGame(User $user, Game $game): bool
    {
        // 1. El user tiene un scorekeeper asociado
        $scorekeeperIds = \App\Models\Scorekeeper::where('user_id', $user->id)->pluck('id');
        if ($scorekeeperIds->isEmpty()) {
            return false;
        }

        // 2. Ese scorekeeper esta en game_scorekeeper para este juego
        return \DB::table('game_scorekeeper')
            ->where('game_id', $game->id)
            ->whereIn('scorekeeper_id', $scorekeeperIds)
            ->exists();
    }
}

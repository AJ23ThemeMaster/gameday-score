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
 * - anotador: dos rutas de acceso (hibrido para piloto):
 *      (a) anotador con scope automatico: ademas de 'anotador' tiene rol
 *          'delegado' + team_id + category_id no nulos. En ese caso su scope
 *          es (su equipo, su categoria) y puede VER + ANOTAR cualquier juego
 *          donde (home_team_id == team_id OR away_team_id == team_id) Y
 *          category_id == category_id. No requiere asignacion game_scorekeeper.
 *      (b) anotador puro: solo puede VER + ANOTAR los juegos donde esta
 *          asignado (game_scorekeeper.scorekeeper_id apunta a un scorekeeper
 *          cuyo user_id == $user->id).
 * - resto: solo lectura si son owner del juego.
 *
 * Piloto: el camino (a) existe para que un delegado del equipo pueda tomar
 * el rol anotador sin que el admin tenga que crear un Scorekeeper y
 * asignarlo a cada juego. El 'isDelegado()' exige AMBOS FK (team + cat)
 * no nulos, asi que un delegado "flotante" sin scope nunca habilita el
 * camino (a) por accidente.
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
        return $user->hasRole('admin');
    }

    public function update(User $user, Game $game): bool
    {
        // Mismas reglas que view: anotador puede 'editar' (cambiar roster,
        // reordenar lineup, sustituir) en cualquier juego al que tenga acceso.
        return $this->userCanAccessGame($user, $game, requireOwner: true);
    }

    public function delete(User $user, Game $game): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Anotar jugadas en vivo (pitch, end-inning, end-game, substitute, runner action).
     *
     * Admin: puede anotar cualquier juego.
     * Anotador con scope automatico (delegado): juegos de (su equipo, su categoria).
     * Anotador puro: solo donde esta asignado via game_scorekeeper.
     * Otros: no.
     */
    public function score(User $user, Game $game): bool
    {
        return $this->userCanAccessGame($user, $game, requireOwner: false);
    }

    /**
     * Chequeo unificado de acceso a juego.
     *
     * @param bool $requireOwner Si true, un usuario con rol 'gestor' que no
     *                           tenga scope de juego podra ver SOLO sus
     *                           propios juegos (compat con el comportamiento
     *                           anterior que permitia 'owner del juego').
     *                           Para 'score' pasamos false porque los
     *                           anotadores de scope ajeno al juego owner
     *                           deben poder anotar igual.
     */
    protected function userCanAccessGame(User $user, Game $game, bool $requireOwner): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('anotador')) {
            // (a) anotador con scope automatico por delegacion
            if ($user->hasRole('delegado') && $this->userInDelegateScope($user, $game)) {
                return true;
            }
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
        if ($user->team_id === null || $user->category_id === null) {
            return false;
        }
        $inTeamScope = (int) $game->home_team_id === (int) $user->team_id
            || (int) $game->away_team_id === (int) $user->team_id;

        return $inTeamScope && (int) $game->category_id === (int) $user->category_id;
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

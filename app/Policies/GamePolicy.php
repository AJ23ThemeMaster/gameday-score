<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Game;
use App\Models\User;

/**
 * DISI-15: Politica de acceso a juegos.
 *
 * Reglas:
 * - admin: gestiona todo (CRUD + anotar en cualquier juego)
 * - anotador: solo puede VER y ANOTAR en juegos donde esta asignado
 *              (game_scorekeeper.scorekeeper_id apunta a un scorekeeper
 *              cuyo user_id == auth()->id())
 * - resto: solo lectura (solo si son owner del juego o tienen permiso)
 */
class GamePolicy
{
    /**
     * Ver un juego (scoreboard, detalle, roster).
     */
    public function view(User $user, Game $game): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        if ($user->hasRole('anotador') && $this->userIsAssignedToGame($user, $game)) {
            return true;
        }
        // Owner del juego puede ver
        return $user->id === $game->user_id;
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
        if ($user->hasRole('admin')) {
            return true;
        }
        if ($user->hasRole('anotador') && $this->userIsAssignedToGame($user, $game)) {
            return true;
        }
        return $user->id === $game->user_id;
    }

    public function delete(User $user, Game $game): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Anotar jugadas en vivo (pitch, end-inning, end-game, substitute).
     *
     * Admin: puede anotar cualquier juego.
     * Anotador: solo donde esta asignado.
     * Otros: no.
     */
    public function score(User $user, Game $game): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        if ($user->hasRole('anotador') && $this->userIsAssignedToGame($user, $game)) {
            return true;
        }
        return false;
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

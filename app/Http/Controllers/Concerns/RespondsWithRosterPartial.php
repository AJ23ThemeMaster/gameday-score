<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Models\Game;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Helper para que el RosterController devuelva JSON (con partial HTML) en
 * peticiones AJAX o Redirect en peticiones normales. Asi las vistas
 * pueden usar fetch() sin recargar la pagina y mantener compatibilidad
 * con form submits clasicos.
 */
trait RespondsWithRosterPartial
{
    /**
     * Renderiza el partial del roster (2 tablas Local/Visitante + pitchers).
     * Reutilizado por el index() y por todas las acciones AJAX que
     * necesitan devolver el estado actualizado del roster.
     */
    protected function renderRosterPartial(Game $game): string
    {
        $game->load([
            'category', 'stadium', 'homeTeam', 'awayTeam',
            'homeTeam.athletes', 'awayTeam.athletes',
        ]);

        $rosterEntries = $game->athletes()
            ->withPivot([
                'team_id', 'lineup_order', 'position',
                'is_starter', 'is_pitcher', 'pitches_thrown',
                'at_bats', 'hits', 'runs', 'rbi',
            ])
            ->get()
            ->keyBy('id');

        $homeAvailable = $game->homeTeam->athletes->whereNotIn('id', $rosterEntries->keys());
        $awayAvailable = $game->awayTeam->athletes->whereNotIn('id', $rosterEntries->keys());

        $homePitcher = $rosterEntries->first(fn ($a) => $a->pivot->is_pitcher && $a->pivot->team_id === $game->home_team_id);
        $awayPitcher = $rosterEntries->first(fn ($a) => $a->pivot->is_pitcher && $a->pivot->team_id === $game->away_team_id);

        return view('games.roster._partial', [
            'game' => $game,
            'rosterEntries' => $rosterEntries,
            'homeAvailable' => $homeAvailable,
            'awayAvailable' => $awayAvailable,
            'homePitcher' => $homePitcher,
            'awayPitcher' => $awayPitcher,
        ])->render();
    }

    /**
     * Devuelve JSON si la peticion es AJAX, o redirect con flash si no.
     *
     * @param  array<string, mixed>  $payload  Datos extra a incluir en JSON (errors, fields, etc.)
     */
    protected function rosterResponse(
        Request $request,
        Game $game,
        string $message,
        string $level = 'success',
        array $payload = [],
    ): JsonResponse|\Illuminate\Http\RedirectResponse {
        if ($request->expectsJson() || $request->ajax()) {
            $html = $this->renderRosterPartial($game);

            return response()->json(array_merge([
                'success' => $level === 'success',
                'level' => $level,
                'message' => $message,
                'html' => $html,
            ], $payload));
        }

        return redirect()
            ->route('games.roster.index', $game)
            ->with($level === 'error' ? 'error' : 'status', $message);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Athlete;
use App\Models\Game;
use App\Models\Play;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicGameController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $game = Game::with([
            'category', 'stadium', 'homeTeam', 'awayTeam',
            'scorekeepers', 'referees',
        ])
            ->where('public_token', $token)
            ->where('is_public', true)
            ->firstOrFail();

        // DISI-48: feed jugada por jugada agrupado por inning (top/bottom).
        // Cada pitch se incluye (bola, strike, foul) ademas de outs, hits,
        // walks, bunts, balks, robos y cierres de inning. La vista Blade
        // se encarga del render. El grouping se hace aqui para que la vista
        // no tenga logica de negocio.
        //
        // DISI-52: el orden de la query es ASC por (inning, half, sequence)
        // = orden cronologico (de la mas vieja a la mas nueva). El `sequence`
        // se reinicia por (inning, half) cada vez que arranca un medio inning,
        // asi que dentro de cada columna (top o bottom) el orden refleja
        // exactamente el avance real del juego. NO invertir el orden en este
        // controller ni en la vista; el usuario espera ver "lo primero que
        // paso arriba, lo ultimo que paso abajo" dentro de cada columna.
        $plays = Play::where('game_id', $game->id)
            ->with(['batter', 'pitcher'])
            ->orderBy('inning')
            ->orderBy('half')
            ->orderBy('sequence')
            ->get();

        $playByPlay = $this->groupPlaysByInning($plays);

        // DISI-53: snapshot del pitcher/bateador actual + sus estadisticas en
        // vivo del juego, para las tarjetas que mostramos arriba de B-S-O.
        // Solo se computa cuando el juego esta en curso (scheduled, paused,
        // completed, suspended, cancelled no tienen pitcher/batter "actual").
        [$currentPitcher, $currentBatter, $pitcherStats, $batterStats] = $game->isInProgress()
            ? $this->buildLiveSnapshot($game)
            : [null, null, $this->emptyPitcherStats(), $this->emptyBatterStats()];

        return view('public.games.show', compact(
            'game', 'playByPlay', 'currentPitcher', 'currentBatter', 'pitcherStats', 'batterStats'
        ));
    }

    /**
     * Agrupa las jugadas por inning y half en una estructura lista para iterar en Blade:
     * [
     *   ['inning' => 1, 'top' => [Play, ...], 'bottom' => [Play, ...]],
     *   ['inning' => 2, 'top' => [...], 'bottom' => [...]],
     *   ...
     * ]
     *
     * Cada jugada recibe el atributo dinamico `display_bat_order` (int) con el
     * contador de bateadores en esa mitad dentro del inning actual. Sirve para
     * que la vista muestre "Bateador {nro_orden} al bate" cuando el bateador
     * no esta identificado (placeholder Corredor o batter_id null). El contador
     * se incrementa cada vez que aparece un at_bat_start y se resetea al
     * cambiar de inning o de half.
     *
     * Filtra jugadas tipo `inning_end` y `game_end` (son marcadores internos
     * que el motor graba con el inning/half NUEVO, no el que acaba de cerrar,
     * asi que aparecerian en el bucket incorrecto). Los headers de inning/half
     * ya dan la estructura visual sin necesidad de estos marcadores.
     */
    private function groupPlaysByInning(\Illuminate\Database\Eloquent\Collection $plays): array
    {
        $grouped = [];
        $counter = ['top' => 0, 'bottom' => 0];
        $currentInning = null;
        $currentHalf = null;

        foreach ($plays as $p) {
            // Saltar marcadores de cierre: el cambio de inning/half ya es
            // visible en los headers y en la transicion top->bottom.
            if (in_array($p->type, [Play::TYPE_INNING_END, Play::TYPE_GAME_END], true)) {
                continue;
            }

            $half = $p->half === 'top' ? 'top' : 'bottom';

            // Resetear el contador cuando cambia inning o half (ej. al pasar de
            // top a bottom dentro del mismo inning, o al ir al inning siguiente).
            if ($currentInning !== (int) $p->inning || $currentHalf !== $half) {
                $counter[$half] = 0;
                $currentInning = (int) $p->inning;
                $currentHalf = $half;
            }

            // Incrementar el contador de bateadores al inicio de cada turno.
            if ($p->type === Play::TYPE_PITCH && $p->subtype === 'at_bat_start') {
                $counter[$half]++;
            }

            // Asignar el contador a la jugada para que la vista pueda usarlo
            // como fallback cuando el bateador no esta identificado.
            $p->setAttribute('display_bat_order', $counter[$half]);

            $grouped[$currentInning][$half][] = $p;
        }

        ksort($grouped);
        $result = [];
        foreach ($grouped as $inning => $halves) {
            $result[] = [
                'inning' => $inning,
                'top' => $halves['top'] ?? [],
                'bottom' => $halves['bottom'] ?? [],
            ];
        }

        return $result;
    }

    /**
     * DISI-53: snapshot en vivo del pitcher/bateador actuales y sus estadisticas
     * del juego. Es un subconjunto del `ScoreboardController::buildSnapshot()`
     * (solo los campos que necesita la vista publica — sin on-deck, sin
     * runners, sin summary). Mantiene la misma logica de prioridad:
     *
     *  1. ultima jugada cerro medio inning/game_end: primer bateador del nuevo half + su pitcher.
     *  2. ultima jugada cambio de bateador (walk, out, hit, etc): avanzar al siguiente.
     *  3. sin plays: primer bateador + pitcher del lineup.
     *
     * @return array{0: ?Athlete, 1: ?Athlete, 2: array, 3: array}
     */
    private function buildLiveSnapshot(Game $game): array
    {
        $state = Play::currentState($game->id);

        $lastType = $state['last_play_type'] ?? null;
        $halfChanged = in_array($lastType, [Play::TYPE_INNING_END, Play::TYPE_GAME_END], true);
        $batterChanged = in_array($lastType, [
            Play::TYPE_WALK,
            Play::TYPE_OUT,
            Play::TYPE_HIT,
            Play::TYPE_HBP,
            Play::TYPE_ERROR,
            Play::TYPE_BUNT,
        ], true);

        $engine = app(\App\Services\GameplayEngine::class);

        if ($halfChanged) {
            $state['current_batter_id'] = $engine->firstBatter($game, $state['half']);
            $defendingTeamId = $state['half'] === 'top' ? $game->home_team_id : $game->away_team_id;
            $state['current_pitcher_id'] = $engine->pitcherFor($game, $defendingTeamId);
        } elseif ($batterChanged && $state['current_batter_id']) {
            $state['current_batter_id'] = $engine->nextBatter(
                $game, $state['half'], $state['current_batter_id']
            );
        } elseif (! $state['last_play_id']) {
            $state['current_batter_id'] = $engine->firstBatter($game, $state['half']);
            $defendingTeamId = $state['half'] === 'top' ? $game->home_team_id : $game->away_team_id;
            $state['current_pitcher_id'] = $engine->pitcherFor($game, $defendingTeamId);
        }

        $pitcher = $state['current_pitcher_id'] ? Athlete::find($state['current_pitcher_id']) : null;
        $batter = $state['current_batter_id'] ? Athlete::find($state['current_batter_id']) : null;

        $pitcherStats = $pitcher
            ? Play::statsForPitcher($game->id, $pitcher->id)
            : $this->emptyPitcherStats();
        $batterStats = $batter
            ? Play::statsForBatter($game->id, $batter->id)
            : $this->emptyBatterStats();

        return [$pitcher, $batter, $pitcherStats, $batterStats];
    }

    private function emptyPitcherStats(): array
    {
        return ['pitches' => 0, 'strikes' => 0, 'balls' => 0, 'strikeouts' => 0, 'hits' => 0, 'walks' => 0];
    }

    private function emptyBatterStats(): array
    {
        return ['at_bats' => 0, 'hits' => 0, 'strikeouts' => 0, 'walks' => 0, 'avg' => 0.0];
    }

    public function stateJson(Request $request, string $token): JsonResponse
    {
        $game = Game::with(['homeTeam', 'awayTeam'])
            ->where('public_token', $token)
            ->where('is_public', true)
            ->firstOrFail();

        return response()->json([
            'status' => $game->status,
            'current_inning' => $game->current_inning,
            'inning_half' => $game->inning_half,
            'balls' => $game->balls,
            'strikes' => $game->strikes,
            'outs' => $game->outs,
            'bases' => $game->bases,
            'home_score' => $game->home_score,
            'away_score' => $game->away_score,
            'home_team' => ['id' => $game->homeTeam->id, 'name' => $game->homeTeam->name, 'logo_url' => $game->homeTeam->logoUrl],
            'away_team' => ['id' => $game->awayTeam->id, 'name' => $game->awayTeam->name, 'logo_url' => $game->awayTeam->logoUrl],
            'updated_at' => $game->updated_at->toIso8601String(),
        ]);
    }
}

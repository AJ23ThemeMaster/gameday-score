<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Play;
use App\Models\Athlete;
use App\Services\GameplayEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PlayController extends Controller
{
    /**
     * DISI-piloto: chequeo unificado para acciones de anotacion.
     * Antes (DISI-38) cada metodo repetia el patron "admin OR owner" con
     * abort_unless, lo cual excluia al rol anotador. Ahora la policy
     * GamePolicy::score() gobierna: admin + owner + anotador con scope
     * (delegado+team+cat) + anotador asignado.
     */
    private function authorizeScoring(Game $game): void
    {
        $this->authorize('score', $game);
    }

    /**
     * DISI-piloto: chequeo para ver el feed de jugadas. Mismas reglas
     * que la policy view() — admin + owner + anotador con scope + anotador
     * asignado. Antes solo admin/owner.
     */
    private function authorizeViewing(Game $game): void
    {
        $this->authorize('view', $game);
    }

    /**
     * Feed de jugadas (play-by-play) del juego.
     * Devuelve el HTML del listado para refresco AJAX.
     */
    public function index(Request $request, Game $game): View|JsonResponse
    {
        $this->authorizeViewing($game);

        $plays = Play::where('game_id', $game->id)
            ->with(['batter', 'pitcher'])
            ->orderBy('inning')
            ->orderBy('half')
            ->orderBy('sequence')
            ->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'plays' => $plays->map(fn ($p) => [
                    'id' => $p->id,
                    'inning' => $p->inning,
                    'half' => $p->half,
                    'sequence' => $p->sequence,
                    'type' => $p->type,
                    'subtype' => $p->subtype,
                    'result' => $p->result,
                    'batter' => $p->batter?->full_name,
                    'pitcher' => $p->pitcher?->full_name,
                    'runs_scored' => $p->runs_scored,
                    'recorded_at' => $p->recorded_at->toIso8601String(),
                ])->values(),
            ]);
        }

        return view('games.plays.index', compact('game', 'plays'));
    }

    /**
     * Registrar una jugada. Usado por las fases siguientes (Pitcheo, Bateo, Extras).
     * En Fase 1 solo el endpoint existe; la UI para llamar a este endpoint
     * se construye en fases posteriores.
     */
    public function store(Request $request, Game $game): JsonResponse
    {
        $this->authorizeScoring($game);

        $validated = $request->validate([
            'inning' => ['required', 'integer', 'min:1'],
            'half' => ['required', 'in:top,bottom'],
            'type' => ['required', 'string', 'in:' . implode(',', [
                Play::TYPE_PITCH, Play::TYPE_OUT, Play::TYPE_HIT,
                Play::TYPE_WALK, Play::TYPE_HBP, Play::TYPE_ERROR,
                Play::TYPE_BUNT, Play::TYPE_BALK, Play::TYPE_RUNNER_MOVEMENT,
                Play::TYPE_SUBSTITUTION, Play::TYPE_INNING_END, Play::TYPE_GAME_END,
            ])],
            'subtype' => ['nullable', 'string', 'max:30'],
            'result' => ['nullable', 'string', 'max:60'],
            'batter_id' => ['nullable', 'exists:athletes,id'],
            'pitcher_id' => ['nullable', 'exists:athletes,id'],
            'outs_before' => ['nullable', 'integer', 'min:0', 'max:2'],
            'outs_after' => ['nullable', 'integer', 'min:0', 'max:3'],
            'balls' => ['nullable', 'integer', 'min:0', 'max:4'],
            'strikes' => ['nullable', 'integer', 'min:0', 'max:3'],
            'runs_scored' => ['nullable', 'integer', 'min:0'],
            'rbi' => ['nullable', 'integer', 'min:0'],
            'bases_before' => ['nullable', 'array'],
            'bases_after' => ['nullable', 'array'],
            'meta' => ['nullable', 'array'],
        ]);

        // Siguiente sequence
        $sequence = Play::where('game_id', $game->id)
            ->where('inning', $validated['inning'])
            ->where('half', $validated['half'])
            ->max('sequence') + 1;

        $play = Play::create(array_merge($validated, [
            'game_id' => $game->id,
            'sequence' => $sequence,
            'recorded_by' => Auth::id(),
            'recorded_at' => now(),
            'outs_before' => $validated['outs_before'] ?? 0,
            'outs_after' => $validated['outs_after'] ?? 0,
            'balls' => $validated['balls'] ?? 0,
            'strikes' => $validated['strikes'] ?? 0,
            'runs_scored' => $validated['runs_scored'] ?? 0,
            'rbi' => $validated['rbi'] ?? 0,
        ]));

        return response()->json([
            'success' => true,
            'play' => $play,
            'message' => 'Jugada registrada.',
        ], 201);
    }

    /**
     * Procesa un evento de pitcheo (ball, strike, foul, out) via GameplayEngine.
     * Usado por la UI del scoreboard en Fases 2-5.
     */
    public function pitch(Request $request, Game $game, GameplayEngine $engine): JsonResponse
    {
        $this->authorizeScoring($game);
        abort_unless($game->isInProgress(), 422, 'El juego no esta en curso.');

        $validated = $request->validate([
            'type' => ['required', 'in:ball,strike,foul,out,hit,balk,bunt'],
            'subtype' => ['nullable', 'string', 'max:30'],
            'defensive_sequence' => ['nullable', 'array', 'max:5'],
            'defensive_sequence.*' => ['string', 'max:10'],
        ]);

        $result = $engine->processPitch($game, $validated);

        $summary = null;
        if ($result['end_half'] ?? false) {
            // Calcular resumen del inning que se cerro naturalmente.
            // endHalf cierra el half donde se produjo la jugada. Pero el state
            // ya leera el half NUEVO (porque advanceBatter lo actualiza). Asi
            // que el inning que se cerro es el half NUEVO - 1.
            $closedInning = $result['state']['inning'];
            $closedHalf = $result['state']['half'];
            if ($closedHalf === 'top') {
                // El nuevo half es top, pero el cerrado fue el bottom del inning - 1.
                $closedInning = max(1, $closedInning - 1);
                $closedHalf = 'bottom';
            } else {
                // El nuevo half es bottom, pero el cerrado fue el top del mismo inning.
                $closedHalf = 'top';
            }
            $summary = $engine->inningSummary($game->fresh(), $closedInning, $closedHalf);
        }

        // Snapshot UI: pitcher/batter/on-deck/stats actuales (necesario para que el
        // scoreboard-v2 actualice la UI sin recargar la pagina).
        $snapshot = $this->buildUiSnapshot($engine, $game->fresh(), $result['state']);

        return response()->json([
            'success' => true,
            'state' => $result['state'],
            'score' => Play::scoreboard($game->id), // DISI-21: incluir score para reflejar carreras inmediatamente
            'walk' => $result['walk'] ?? false,
            'strikeout' => $result['strikeout'] ?? false,
            'end_half' => $result['end_half'] ?? false,
            'summary' => $summary,
            'pitcher' => $snapshot['pitcher'],
            'batter' => $snapshot['batter'],
            'on_deck' => $snapshot['on_deck'],
            'pitcher_stats' => $snapshot['pitcher_stats'],
            'batter_stats' => $snapshot['batter_stats'],
            // Bases con atletas completos (no solo IDs) para que el scoreboard-v2
            // muestre nombre + dorsal del corredor en cada base tras embasar / out / etc.
            'bases' => $this->basesToAthletes($result['state']['bases'] ?? []),
            'is_completed' => $game->fresh()->isCompleted(),
            'plays' => collect($result['plays'])->map(fn ($p) => [
                'id' => $p->id,
                'type' => $p->type,
                'subtype' => $p->subtype,
                'result' => $p->result,
            ])->values(),
        ]);
    }

    /**
     * Finaliza la media entrada actual manualmente (cierre por el anotador
     * antes de los 3 outs, shortened game, lluvia, etc.).
     */
    public function endInning(Request $request, Game $game, GameplayEngine $engine): JsonResponse
    {
        // DISI-36: mensajes explicitos para que la UI muestre el motivo real
        // del error (403/422 silencioso era inutil para diagnosticar).
        //
        // DISI-37 + DISI-piloto: permitir tambien al rol anotador (con scope
        // automatico o asignado). Antes solo admin/owner.
        $this->authorizeScoring($game);
        abort_unless(
            $game->isInProgress(),
            422,
            'El juego ya esta finalizado. Estado actual: "' . $game->status . '".'
        );

        $result = $engine->endInning($game);
        $summary = null;
        if (isset($result['inning']) && isset($result['half']) && ($result['status'] ?? '') === 'inning_closed') {
            $summary = $engine->inningSummary($game->fresh(), $result['inning'], $result['half']);
        }

        // Snapshot UI: pitcher/batter/on-deck/stats actuales despues del endInning.
        $freshGame = $game->fresh();
        $stateForUi = [
            'inning' => $result['inning'] ?? $freshGame->current_inning,
            'half' => $result['half'] ?? ($freshGame->inning_half ?? 'top'),
            'bases' => ['first' => null, 'second' => null, 'third' => null],
            'current_batter_id' => $result['batter_id'] ?? null,
            'current_pitcher_id' => $result['pitcher_id'] ?? null,
        ];
        $snapshot = $this->buildUiSnapshot($engine, $freshGame, $stateForUi);

        return response()->json([
            'success' => true,
            'result' => $result,
            'summary' => $summary,
            'pitcher' => $snapshot['pitcher'],
            'batter' => $snapshot['batter'],
            'on_deck' => $snapshot['on_deck'],
            'pitcher_stats' => $snapshot['pitcher_stats'],
            'batter_stats' => $snapshot['batter_stats'],
            'is_completed' => $freshGame->isCompleted(),
            'state' => $stateForUi,
            'score' => Play::scoreboard($freshGame->id),
        ]);
    }

    /**
     * Finaliza el juego manualmente (mercy rule, lluvia, etc.).
     */
    public function endGame(Request $request, Game $game, GameplayEngine $engine): JsonResponse
    {
        // DISI-36: mensajes explicitos para que la UI muestre el motivo real
        // del error (403/422 silencioso era inutil para diagnosticar).
        //
        // DISI-37 + DISI-piloto: permitir tambien al rol anotador (con scope
        // automatico o asignado). Antes solo admin/owner.
        $this->authorizeScoring($game);
        abort_unless(
            $game->isInProgress(),
            422,
            'El juego ya esta finalizado. Estado actual: "' . $game->status . '".'
        );

        $result = $engine->endGame($game);

        // Snapshot UI despues del endGame.
        $freshGame = $game->fresh();
        $stateForUi = [
            'inning' => $freshGame->current_inning,
            'half' => $freshGame->inning_half ?? 'top',
            'bases' => ['first' => null, 'second' => null, 'third' => null],
            'current_batter_id' => null,
            'current_pitcher_id' => null,
        ];
        $snapshot = $this->buildUiSnapshot($engine, $freshGame, $stateForUi);

        return response()->json([
            'success' => true,
            'result' => $result,
            'pitcher' => $snapshot['pitcher'],
            'batter' => $snapshot['batter'],
            'on_deck' => $snapshot['on_deck'],
            'pitcher_stats' => $snapshot['pitcher_stats'],
            'batter_stats' => $snapshot['batter_stats'],
            'is_completed' => $freshGame->isCompleted(),
            'state' => $stateForUi,
            'score' => Play::scoreboard($freshGame->id),
        ]);
    }

    /**
     * Registra una sustitucion de pitcher, bateador o pinch runner.
     */
    public function substitute(Request $request, Game $game, GameplayEngine $engine): JsonResponse
    {
        // DISI-38 + DISI-piloto: permitir tambien al rol anotador (con scope o asignado).
        $this->authorizeScoring($game);
        abort_unless($game->isInProgress(), 422, 'El juego no esta en curso.');

        $validated = $request->validate([
            'kind' => ['required', 'in:pitcher,batter,pr'],
            'out_athlete_id' => ['required', 'integer', 'exists:athletes,id'],
            'in_athlete_id' => ['required', 'integer', 'exists:athletes,id', 'different:out_athlete_id'],
            'base' => ['required_if:kind,pr', 'nullable', 'in:first,second,third'],
        ]);

        try {
            $result = $engine->substitute(
                $game,
                $validated['kind'],
                (int) $validated['out_athlete_id'],
                (int) $validated['in_athlete_id'],
                $validated['base'] ?? null,
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'result' => $result,
        ]);
    }

    /**
     * DISI-20: Accion del anotador sobre un corredor identificado en una base.
     * El frontend envia {base, action} y el engine crea la jugada correspondiente
     * (avance, robo, wild pitch, passed ball, OBS, anotada con/sin RBI, out
     * por robo/pickoff, out al intentar avanzar a 2B/3B).
     */
    public function runnerAction(Request $request, Game $game, GameplayEngine $engine): JsonResponse
    {
        // DISI-38 + DISI-piloto: permitir tambien al rol anotador (con scope o asignado).
        $this->authorizeScoring($game);
        abort_unless($game->isInProgress(), 422, 'El juego no esta en curso.');

        $validated = $request->validate([
            'base' => ['required', 'in:first,second,third'],
            'action' => ['required', 'in:advance,stolen_base,wild_pitch,passed_ball,error_advance,obstruction,score_rbi,score_no_rbi,caught_stealing,pickoff,out_at_2b,out_at_3b,out_at_advance'],
        ]);

        try {
            $result = $engine->runnerAction($game, $validated['base'], $validated['action']);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'result' => $result,
        ]);
    }

    /**
     * Construye el snapshot UI (pitcher/batter/on-deck/stats) en el formato
     * JSON-friendly que consume el scoreboard-v2 via applyState().
     *
     * Replica la logica de ScoreboardController::buildSnapshot() sin depender
     * del controller (evita inyeccion cruzada). El parametro $state puede
     * traer current_batter_id/current_pitcher_id; si faltan, se resuelven
     * desde el engine (firstBatter/pitcherFor) usando el half del state.
     */
    private function buildUiSnapshot(GameplayEngine $engine, Game $game, array $state): array
    {
        $half = $state['half'] ?? ($game->inning_half ?? 'top');
        $currentBatterId = $state['current_batter_id'] ?? null;
        $currentPitcherId = $state['current_pitcher_id'] ?? null;

        // Si no hay bateador/pitcher actuales, resolver desde el lineup.
        if (! $currentBatterId) {
            $currentBatterId = $engine->firstBatter($game, $half);
        }
        if (! $currentPitcherId) {
            $defendingTeamId = $half === 'top' ? $game->home_team_id : $game->away_team_id;
            $currentPitcherId = $engine->pitcherFor($game, $defendingTeamId);
        }

        $pitcher = $currentPitcherId ? Athlete::find($currentPitcherId) : null;
        $batter = $currentBatterId ? Athlete::find($currentBatterId) : null;

        // On-deck: siguiente bateador del lineup (el engine respeta el wrap 9->1).
        $onDeckId = $engine->nextBatter($game, $half, $currentBatterId);
        $onDeck = $onDeckId ? Athlete::find($onDeckId) : null;

        $pitcherStats = $pitcher ? Play::statsForPitcher($game->id, $pitcher->id)
            : ['pitches' => 0, 'strikes' => 0, 'balls' => 0, 'strikeouts' => 0, 'hits' => 0, 'walks' => 0];
        $batterStats = $batter ? Play::statsForBatter($game->id, $batter->id)
            : ['at_bats' => 0, 'hits' => 0, 'strikeouts' => 0, 'walks' => 0, 'avg' => 0.0];

        return [
            'pitcher' => $pitcher ? $this->athleteToArray($pitcher) : null,
            'batter' => $batter ? $this->athleteToArray($batter) : null,
            'on_deck' => $onDeck ? $this->athleteToArray($onDeck) : null,
            'pitcher_stats' => $pitcherStats,
            'batter_stats' => $batterStats,
        ];
    }

    private function athleteToArray(Athlete $a): array
    {
        $firstInitial = mb_strtoupper(mb_substr($a->first_name ?? '', 0, 1));
        $lastInitial = mb_strtoupper(mb_substr($a->last_name ?? '', 0, 1));

        return [
            'id' => $a->id,
            'name' => $a->full_name,
            'first_name' => $a->first_name,
            'last_name' => $a->last_name,
            'number' => $a->number,
            'position' => $a->position,
            'team_id' => $a->team?->id,
            'photo_url' => $a->photoUrl,
            'initials' => $firstInitial . $lastInitial,
        ];
    }

    /**
     * Convierte un array de bases (first/second/third con athlete IDs)
     * en un objeto con atletas completos para enviar al cliente.
     * Si la base esta vacia (null), devuelve null para esa posicion.
     *
     * Caso especial: si el engine almaceno el placeholder Play::ANON_RUNNER
     * (= 'corredor') porque no tiene ID del atleta (caso de un corredor que
     * avanzo sin que el engine preservara su ID), devolvemos un objeto
     * atleta minimo para que el diamante muestre la base ocupada con un
     * label generico "Corredor" en vez de quedar vacia.
     */
    private function basesToAthletes(array $stateBases): array
    {
        $out = ['first' => null, 'second' => null, 'third' => null];
        foreach (['first', 'second', 'third'] as $base) {
            $val = $stateBases[$base] ?? null;
            if ($val === null) {
                $out[$base] = null;
            } elseif ($val === Play::ANON_RUNNER) {
                $out[$base] = [
                    'id' => 'corredor',
                    'name' => 'Corredor',
                    'first_name' => '',
                    'last_name' => '',
                    'number' => '?',
                    'position' => null,
                    'team_id' => null,
                    'photo_url' => null,
                    'initials' => '?',
                ];
            } elseif (is_int($val)) {
                $athlete = Athlete::find($val);
                if ($athlete) {
                    $out[$base] = $this->athleteToArray($athlete);
                }
            } else {
                // Cualquier otro caso degenerado: limpiar la base.
                $out[$base] = null;
            }
        }
        return $out;
    }
}

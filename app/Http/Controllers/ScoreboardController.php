<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Athlete;
use App\Models\Game;
use App\Models\Play;
use App\Services\GameplayEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class ScoreboardController extends Controller
{
    public function __construct(private readonly GameplayEngine $engine)
    {
    }

    /**
     * Endpoint de stats historicas del juego (MEJ-3).
     * Devuelve box score acumulado: pitching de TODOS los pitchers que han
     * lanzado, batting de TODOS los bateadores que han bateado, y line score
     * por inning. Usado por el modal "Stats del juego".
     */
    public function stats(Request $request, Game $game): JsonResponse
    {
        $this->authorize('view', $game);

        $game->load('homeTeam', 'awayTeam');
        $totalInnings = (int) ($game->innings_count ?: 7);

        $stats = Play::statsAll($game->id, $game->home_team_id, $game->away_team_id, $totalInnings);

        // Adjuntar nombre y numero del atleta a cada fila
        $pitcherIds = array_column($stats['pitching'], 'pitcher_id');
        $batterIds = array_column($stats['batting'], 'batter_id');
        $allIds = array_unique(array_merge($pitcherIds, $batterIds));
        $athletes = \App\Models\Athlete::whereIn('id', $allIds)->get()->keyBy('id');

        foreach ($stats['pitching'] as &$row) {
            $a = $athletes->get($row['pitcher_id']);
            $row['name'] = $a?->full_name ?? 'ID ' . $row['pitcher_id'];
            $row['number'] = $a?->number;
            $row['team_id'] = $a?->team?->id;
        }
        unset($row);
        foreach ($stats['batting'] as &$row) {
            $a = $athletes->get($row['batter_id']);
            $row['name'] = $a?->full_name ?? 'ID ' . $row['batter_id'];
            $row['number'] = $a?->number;
            $row['team_id'] = $a?->team?->id;
        }
        unset($row);

        return response()->json([
            'success' => true,
            'home_team' => [
                'id' => $game->home_team_id,
                'name' => $game->homeTeam->name,
                'short' => $game->homeTeam->short_name ?? $game->homeTeam->name,
            ],
            'away_team' => [
                'id' => $game->away_team_id,
                'name' => $game->awayTeam->name,
                'short' => $game->awayTeam->short_name ?? $game->awayTeam->name,
            ],
            'total_innings' => $totalInnings,
            'pitching' => $stats['pitching'],
            'batting' => $stats['batting'],
            'line_score' => $stats['line_score'],
        ]);
    }

    /**
     * Endpoint para reordenar + editar el lineup de un equipo (MEJ-4 + DISI-31).
     * Espera un body con:
     *   {
     *     team_id: int,
     *     lineup: [                                          (formato nuevo DISI-31)
     *       { athlete_id, lineup_order, position, is_pitcher },
     *       ...
     *     ]
     *   }
     *
     * DISI-31b: retrocompatibilidad con el formato viejo `order` que solo traia
     * athlete_id y lineup_order (sin position/is_pitcher). Si el frontend envia
     * `order`, lo transformamos a `lineup` tomando los valores de position/is_pitcher
     * ya almacenados en el pivot (no los cambiamos). Asi un bundle viejo en el
     * browser puede seguir guardando el orden de bateo sin romper.
     *
     * Reglas de validacion (DISI-31):
     *  - Exactamente 9 elementos en lineup.
     *  - lineup_order unicos del 1 al 9.
     *  - exactamente 1 is_pitcher=true (solo un pitcher por equipo en el lineup).
     *  - position valida (P/C/1B/2B/3B/SS/LF/CF/RF).
     *  - Los atletas deben pertenecer al roster del juego y del team_id.
     */
    public function reorderLineup(Request $request, Game $game): JsonResponse
    {
        $this->authorize('score', $game);

        // DISI-31b: si llega el formato viejo 'order' (sin position/is_pitcher),
        // aceptarlo y enriquecerlo con los valores actuales del pivot. Asi el
        // bundle del frontend en el server puede seguir funcionando hasta que se
        // regenere con npm run build.
        $payload = $request->all();
        $isLegacyFormat = isset($payload['order']) && ! isset($payload['lineup']);
        if ($isLegacyFormat) {
            $teamIdLegacy = (int) ($payload['team_id'] ?? 0);
            if ($teamIdLegacy === $game->home_team_id || $teamIdLegacy === $game->away_team_id) {
                $existingPivots = DB::table('game_athlete')
                    ->where('game_id', $game->id)
                    ->where('team_id', $teamIdLegacy)
                    ->whereIn('athlete_id', array_column($payload['order'], 'athlete_id'))
                    ->get()
                    ->keyBy('athlete_id');
                $payload['lineup'] = array_map(function ($row) use ($existingPivots) {
                    $pivot = $existingPivots->get($row['athlete_id']);
                    return [
                        'athlete_id' => $row['athlete_id'],
                        'lineup_order' => $row['lineup_order'],
                        'position' => $pivot->position ?? 'LF',
                        'is_pitcher' => (bool) ($pivot->is_pitcher ?? false),
                    ];
                }, $payload['order']);
            }
        }

        $data = $request->validate([
            'team_id' => 'required|integer',
        ]);

        // Validar 'lineup' contra el payload enriquecido (puede venir de `order` legacy).
        $validator = Validator::make($payload, [
            'lineup' => 'required|array|size:9',
            'lineup.*.athlete_id' => 'required|integer',
            'lineup.*.lineup_order' => 'required|integer|min:1|max:9',
            'lineup.*.position' => 'required|string|in:P,C,1B,2B,3B,SS,LF,CF,RF',
            'lineup.*.is_pitcher' => 'required|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validacion fallida',
                'errors' => $validator->errors(),
            ], 422);
        }
        $data = array_merge($data, $validator->validated());

        $teamId = (int) $data['team_id'];
        if ($teamId !== $game->home_team_id && $teamId !== $game->away_team_id) {
            return response()->json(['success' => false, 'error' => 'Equipo no pertenece al juego'], 422);
        }

        // Validar lineup_order unicos 1..9
        $orders = array_column($data['lineup'], 'lineup_order');
        if (count(array_unique($orders)) !== 9) {
            return response()->json(['success' => false, 'error' => 'lineup_order debe ser unico del 1 al 9'], 422);
        }

        // Validar exactamente 1 pitcher (DISI-31b: en formato legacy sin pitcher
        // definido, autoasignar al primer bateador como fallback).
        $pitcherCount = count(array_filter($data['lineup'], fn ($r) => $r['is_pitcher']));
        if ($pitcherCount === 0) {
            if ($isLegacyFormat) {
                // Autoasignar pitcher al primer bateador (convención amateur).
                $data['lineup'][0]['is_pitcher'] = true;
            } else {
                return response()->json(['success' => false, 'error' => 'Debe haber exactamente 1 pitcher en el lineup'], 422);
            }
        } elseif ($pitcherCount !== 1) {
            return response()->json(['success' => false, 'error' => 'Debe haber exactamente 1 pitcher en el lineup'], 422);
        }

        // Validar athlete_ids pertenecen al roster del equipo
        $athleteIds = array_column($data['lineup'], 'athlete_id');
        $validIds = DB::table('game_athlete')
            ->where('game_id', $game->id)
            ->where('team_id', $teamId)
            ->whereIn('athlete_id', $athleteIds)
            ->pluck('athlete_id')
            ->all();
        if (count($validIds) !== 9) {
            return response()->json(['success' => false, 'error' => 'Uno o mas atletas no pertenecen al roster del equipo'], 422);
        }

        DB::transaction(function () use ($game, $teamId, $data) {
            foreach ($data['lineup'] as $row) {
                $game->athletes()
                    ->wherePivot('team_id', $teamId)
                    ->updateExistingPivot($row['athlete_id'], [
                        'lineup_order' => (int) $row['lineup_order'],
                        'position' => $row['position'],
                        'is_pitcher' => (bool) $row['is_pitcher'],
                        'is_starter' => 1,
                    ]);
            }

            // Los atletas que estaban en el lineup pero fueron quitados pasan a bench
            // (is_starter=0, lineup_order=null, position=null, is_pitcher=false).
            $currentLineupIds = array_column($data['lineup'], 'athlete_id');
            $benchedIds = DB::table('game_athlete')
                ->where('game_id', $game->id)
                ->where('team_id', $teamId)
                ->where('is_starter', true)
                ->whereNotIn('athlete_id', $currentLineupIds)
                ->pluck('athlete_id')
                ->all();
            foreach ($benchedIds as $athleteId) {
                $game->athletes()
                    ->wherePivot('team_id', $teamId)
                    ->updateExistingPivot($athleteId, [
                        'lineup_order' => null,
                        'position' => null,
                        'is_pitcher' => false,
                        'is_starter' => false,
                    ]);
            }
        });

        // DISI-31d: sincronizar el pitcher de la ultima jugada at_bat_start del
        // half actual con el pivot (fuente de verdad). Asi el proximo
        // currentState() (que lee pitcher_id de la ultima jugada) devuelve el
        // pitcher nuevo cuando se cambia via modal de lineup (sin pasar por
        // processPitch). Si no hay jugada at_bat_start todavia (juego nuevo),
        // no hacemos nada: buildSnapshot ya recalcula desde el pivot en ese caso.
        $game->refresh();
        $half = $game->inning_half ?? 'top';
        $defendingTeamId = $half === 'top' ? $game->home_team_id : $game->away_team_id;
        $newPitcherId = DB::table('game_athlete')
            ->where('game_id', $game->id)
            ->where('team_id', $defendingTeamId)
            ->where('is_pitcher', true)
            ->value('athlete_id');
        if ($newPitcherId) {
            DB::table('plays')
                ->where('game_id', $game->id)
                ->where('inning', $game->current_inning)
                ->where('half', $half)
                ->where('subtype', 'at_bat_start')
                ->orderByDesc('id')
                ->limit(1)
                ->update(['pitcher_id' => (int) $newPitcherId]);
        }

        // DISI-31c: devolver el estado REAL del pivot en la BD (no el input del
        // request), con tipos correctos (int/bool). Asi el frontend sabe exactamente
        // lo que quedo guardado, y los atletas quitados aparecen como benched.
        $pivots = DB::table('game_athlete')
            ->where('game_id', $game->id)
            ->where('team_id', $teamId)
            ->where('is_starter', true)
            ->orderBy('lineup_order')
            ->get(['athlete_id', 'lineup_order', 'position', 'is_pitcher'])
            ->map(fn ($r) => [
                'athlete_id' => (int) $r->athlete_id,
                'lineup_order' => (int) $r->lineup_order,
                'position' => $r->position,
                'is_pitcher' => (bool) $r->is_pitcher,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Lineup actualizado',
            'lineup' => $pivots,
            'mode' => $isLegacyFormat ? 'legacy' : 'full',
        ]);
    }

    /**
     * Scoreboard principal (vista del anotador). Reemplaza al antiguo games.live
     * con un layout moderno: logos, scores, rombo con corredores, info del
     * pitcher/batter, count (B/S/O), y 3 tabs de acciones (PITCHEo / BATEo / EXTRAS).
     */
    public function show(Request $request, Game $game): View|JsonResponse
    {
        $this->authorize('view', $game);

        $game->load([
            'category', 'tournament', 'tournament.league', 'stadium',
            'homeTeam', 'awayTeam',
            'homeTeam.athletes', 'awayTeam.athletes',
        ]);

        [$state, $pitcher, $batter, $onDeck, $pitcherStats, $batterStats] =
            $this->buildSnapshot($game);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'state' => $state,
                'score' => Play::scoreboard($game->id),
                'pitcher' => $pitcher ? $this->athleteToArray($pitcher) : null,
                'pitcher_stats' => $pitcherStats,
                'batter' => $batter ? $this->athleteToArray($batter) : null,
                'batter_stats' => $batterStats,
                'on_deck' => $onDeck ? $this->athleteToArray($onDeck) : null,
                'runners' => $this->runners($state),
            ]);
        }

        $score = Play::scoreboard($game->id);
        $runners = $this->runners($state);

        return view('games.scoreboard', compact(
            'game', 'state', 'score', 'pitcher', 'batter', 'onDeck', 'runners',
            'pitcherStats', 'batterStats',
        ));
    }

    /**
     * Live poll endpoint: solo el state JSON, no la vista completa.
     * El cliente lo llama cada 5s para mantener el scoreboard sincronizado.
     */
    public function poll(Request $request, Game $game): JsonResponse
    {
        $this->authorize('view', $game);

        [$state, $pitcher, $batter, $onDeck, $pitcherStats, $batterStats] =
            $this->buildSnapshot($game);

        // Si la ultima jugada cerro un inning (inning_end) o el juego (game_end),
        // incluir el summary del inning que se cerro (no del actual). Esto permite
        // que el cliente muestre el modal de resumen al detectar el cambio.
        $summary = null;
        $lastPlayType = $state['last_play_type'] ?? null;
        if (in_array($lastPlayType, [\App\Models\Play::TYPE_INNING_END, \App\Models\Play::TYPE_GAME_END], true)) {
            // El state.half actual es el NUEVO half. El que se cerro es el opuesto.
            $closedHalf = $state['half'] === 'top' ? 'bottom' : 'top';
            $closedInning = $state['half'] === 'top'
                ? max(1, $state['inning'] - 1)
                : $state['inning'];
            $summary = app(\App\Services\GameplayEngine::class)
                ->inningSummary($game, $closedInning, $closedHalf);
            $summary['closed_inning'] = $closedInning;
            $summary['closed_half'] = $closedHalf;
        }

        return response()->json([
            'state' => $state,
            'score' => Play::scoreboard($game->id),
            'pitcher' => $pitcher ? $this->athleteToArray($pitcher) : null,
            'pitcher_stats' => $pitcherStats,
            'batter' => $batter ? $this->athleteToArray($batter) : null,
            'batter_stats' => $batterStats,
            'on_deck' => $onDeck ? $this->athleteToArray($onDeck) : null,
            'runners' => $this->runners($state),
            'summary' => $summary,
        ]);
    }

    /**
     * Construye el snapshot completo: state + pitcher/batter + on-deck + stats.
     *
     * Casos que requieren recalcular pitcher/bateador del state leido:
     * 1. La ultima jugada cerro el medio inning (inning_end) o el juego (game_end):
     *    la jugada pertenece al half anterior, hay que buscar el pitcher y el
     *    primer bateador del nuevo half.
     * 2. outs === 0 y NO es el inicio del juego (hay plays previos): indica
     *    que el medio inning acaba de empezar (post-walk/strikeout/out o
     *    inicio de inning). Tambien hay que recalcular.
     * 3. La ultima jugada fue un walk, strikeout, out o hit (cambio de bateador
     *    sin cambio de half): el current_batter_id leido de la jugada
     *    corresponde al bateador que ACABA de salir. Hay que avanzar al
     *    siguiente del lineup.
     *
     * @return array{0: array, 1: ?Athlete, 2: ?Athlete, 3: ?Athlete, 4: array, 5: array}
     */
    private function buildSnapshot(Game $game): array
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

        // Orden de prioridad:
        // 1) half cambio (inning_end o game_end): primer bateador + pitcher del nuevo half.
        // 2) bateador salio (walk, strikeout, out, hit, etc.) pero el half continua: avanza.
        // 3) sin plays: primer bateador y pitcher del lineup.
        if ($halfChanged) {
            $state['current_batter_id'] = $this->engine->firstBatter($game, $state['half']);
            $defendingTeamId = $state['half'] === 'top' ? $game->home_team_id : $game->away_team_id;
            $state['current_pitcher_id'] = $this->engine->pitcherFor($game, $defendingTeamId);
        } elseif ($batterChanged && $state['current_batter_id']) {
            $state['current_batter_id'] = $this->engine->nextBatter(
                $game, $state['half'], $state['current_batter_id']
            );
        } elseif (! $state['last_play_id']) {
            $state['current_batter_id'] = $this->engine->firstBatter($game, $state['half']);
            $defendingTeamId = $state['half'] === 'top' ? $game->home_team_id : $game->away_team_id;
            $state['current_pitcher_id'] = $this->engine->pitcherFor($game, $defendingTeamId);
        }

        $pitcher = $state['current_pitcher_id'] ? Athlete::find($state['current_pitcher_id']) : null;
        $batter = $state['current_batter_id'] ? Athlete::find($state['current_batter_id']) : null;

        // On-deck: siguiente bateador del lineup (el engine respeta el wrap 9->1).
        $onDeckId = $this->engine->nextBatter($game, $state['half'], $state['current_batter_id']);
        $onDeck = $onDeckId ? Athlete::find($onDeckId) : null;

        $pitcherStats = $pitcher ? Play::statsForPitcher($game->id, $pitcher->id) : $this->emptyPitcherStats();
        $batterStats = $batter ? Play::statsForBatter($game->id, $batter->id) : $this->emptyBatterStats();

        return [$state, $pitcher, $batter, $onDeck, $pitcherStats, $batterStats];
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
            'team_id' => $a->team?->id,
            'photo_url' => $a->photoUrl,
            'initials' => $firstInitial . $lastInitial,
        ];
    }

    private function emptyPitcherStats(): array
    {
        return ['pitches' => 0, 'strikes' => 0, 'balls' => 0, 'strikeouts' => 0, 'hits' => 0, 'walks' => 0];
    }

    private function emptyBatterStats(): array
    {
        return ['at_bats' => 0, 'hits' => 0, 'strikeouts' => 0, 'walks' => 0, 'avg' => 0.0];
    }

    /**
     * Devuelve los atletas en base: 1B, 2B, 3B.
     */
    private function runners(array $state): array
    {
        $ids = array_filter([
            'first' => $state['bases']['first'] ?? null,
            'second' => $state['bases']['second'] ?? null,
            'third' => $state['bases']['third'] ?? null,
        ]);

        $out = [];
        foreach ($ids as $base => $athleteId) {
            $a = Athlete::find($athleteId);
            if ($a) {
                $out[$base] = $this->athleteToArray($a);
            }
        }

        return $out;
    }

    // ===================================================================
    // DISI-17: Box score inning-by-inning + pitchers + MVP + share image
    // ===================================================================

    /**
     * Vista del box score inning-by-inning con carreras/hits/errores,
     * pitchers (G/P/SV), MVP, y boton para compartir como imagen 1:1.
     */
    public function boxScore(Request $request, Game $game): View
    {
        $this->authorize('view', $game);

        $game->load([
            'category', 'tournament', 'tournament.league', 'stadium',
            'homeTeam', 'awayTeam',
            'winningPitcher', 'losingPitcher', 'savePitcher', 'mvp',
        ]);

        $totalInnings = (int) ($game->innings_count ?: 7);
        $score = Play::scoreboard($game->id);

        // Construir el line score: array por inning (1..N) con R/H/E para local y visitante
        $lineScore = [];
        for ($i = 1; $i <= $totalInnings; $i++) {
            $lineScore[$i] = [
                'home' => $score['by_inning'][$i]['bottom']['R'] ?? 0,
                'away' => $score['by_inning'][$i]['top']['R'] ?? 0,
                'home_h' => $score['by_inning'][$i]['bottom']['H'] ?? 0,
                'away_h' => $score['by_inning'][$i]['top']['H'] ?? 0,
                'home_e' => $score['by_inning'][$i]['bottom']['E'] ?? 0,
                'away_e' => $score['by_inning'][$i]['top']['E'] ?? 0,
            ];
        }

        // Atletas elegibles para pitcher/MVP (todos los de los dos equipos del juego)
        $athletes = Athlete::with('team')
            ->whereIn('team_id', [$game->home_team_id, $game->away_team_id])
            ->orderBy('team_id')
            ->orderBy('number')
            ->get();

        return view('games.box-score', compact(
            'game', 'score', 'lineScore', 'totalInnings', 'athletes'
        ));
    }

    /**
     * Actualizar las atribuciones del juego (pitchers + MVP).
     * Solo admin o anotador del juego.
     */
    public function updateAttributions(Request $request, Game $game)
    {
        $this->authorize('score', $game);

        $data = $request->validate([
            'winning_pitcher_id' => ['nullable', 'integer'],
            'losing_pitcher_id' => ['nullable', 'integer'],
            'save_pitcher_id' => ['nullable', 'integer'],
            'mvp_athlete_id' => ['nullable', 'integer'],
        ]);

        // Validar que los atletas pertenecen a uno de los dos equipos del juego
        $validIds = Athlete::whereIn('team_id', [$game->home_team_id, $game->away_team_id])
            ->pluck('id')
            ->all();

        foreach (['winning_pitcher_id', 'losing_pitcher_id', 'save_pitcher_id', 'mvp_athlete_id'] as $field) {
            if (! empty($data[$field]) && ! in_array((int) $data[$field], $validIds, true)) {
                return back()->withErrors([
                    $field => 'El atleta seleccionado no pertenece a ninguno de los equipos del juego.',
                ])->withInput();
            }
        }

        $game->update($data);

        return back()->with('status', 'Atribuciones actualizadas correctamente.');
    }
}

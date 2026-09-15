<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Athlete;
use App\Models\Game;
use App\Models\Play;
use Illuminate\Support\Facades\DB;

/**
 * Motor de jugadas. Procesa eventos de pitcheo (ball, strike, foul, out)
 * y devuelve el resultado (state + jugada(s) creada(s)).
 *
 * Mantiene la logica centralizada para que:
 *  - el PlayController delegue en el engine
 *  - el ScoreboardController pueda reconstruir el state consistente
 *
 * Reglas basicas (Fase 2 — sin avance automatico de corredores en hits):
 *  - Ball:   balls++. Si balls==4 -> walk: jugada walk, batter a 1B, reset count.
 *  - Strike: strikes++. Si strikes==3 -> strikeout: jugada out+strikeout, next batter, reset.
 *  - Foul:   strikes++ (max 2). Sin out, sin cambio de batter.
 *  - Out:    jugada out con subtype y defensive_sequence. outs++. Si outs==3 -> end half-inning.
 */
class GameplayEngine
{
    /**
     * Procesa un evento de pitcheo.
     *
     * @param  array{type: 'ball'|'strike'|'foul'|'out', subtype?: string, defensive_sequence?: array<int, string>}  $event
     * @return array{state: array, plays: array<int, Play>, walk: bool, strikeout: bool, end_half: bool}
     */
    public function processPitch(Game $game, array $event): array
    {
        return DB::transaction(function () use ($game, $event) {
            $state = Play::currentState($game->id);
            $inning = (int) $state['inning'];
            $half = $state['half'];
            $outs = (int) $state['outs'];
            $balls = (int) $state['balls'];
            $strikes = (int) $state['strikes'];
            $bases = $state['bases'] ?? ['first' => null, 'second' => null, 'third' => null];
            $batterId = $state['current_batter_id'] ?? $this->firstBatter($game, $half);
            $pitcherId = $state['current_pitcher_id'];

            $createdPlays = [];
            $isWalk = false;
            $isStrikeout = false;
            $endHalf = false;

            switch ($event['type']) {
                case 'ball':
                    $balls++;
                    if ($balls >= 4) {
                        $isWalk = true;
                        $basesBefore = $bases;
                        [$bases, $walkRuns] = $this->forceRunnersOnWalk($bases, $batterId);
                        $createdPlays[] = $this->recordPlay($game, [
                            'inning' => $inning, 'half' => $half,
                            'type' => Play::TYPE_WALK,
                            'subtype' => 'walk',
                            'result' => $walkRuns > 0
                                ? "Base por bolas (+{$walkRuns} carrera" . ($walkRuns !== 1 ? 's' : '') . ')'
                                : 'Base por bolas',
                            'batter_id' => $batterId,
                            'pitcher_id' => $pitcherId,
                            'outs_before' => $outs, 'outs_after' => $outs,
                            'balls' => 3, 'strikes' => $strikes,
                            'bases_before' => $basesBefore, 'bases_after' => $bases,
                            'runs_scored' => $walkRuns,
                            'rbi' => $walkRuns,
                        ]);
                        [$batterId, $bases, $half, $inning, $outs, $endHalf, $pitcherId] =
                            $this->advanceBatter($game, $bases, $outs, $half, $inning, $batterId, $pitcherId);
                        $balls = 0;
                        $strikes = 0;
                    } else {
                        $createdPlays[] = $this->recordPlay($game, [
                            'inning' => $inning, 'half' => $half,
                            'type' => Play::TYPE_PITCH,
                            'subtype' => 'ball',
                            'result' => 'Ball',
                            'batter_id' => $batterId,
                            'pitcher_id' => $pitcherId,
                            'outs_before' => $outs, 'outs_after' => $outs,
                            'balls' => $balls, 'strikes' => $strikes,
                            'bases_before' => $bases, 'bases_after' => $bases,
                        ]);
                    }
                    break;

                case 'strike':
                    $strikes++;
                    if ($strikes >= 3) {
                        $isStrikeout = true;
                        $createdPlays[] = $this->recordPlay($game, [
                            'inning' => $inning, 'half' => $half,
                            'type' => Play::TYPE_OUT,
                            'subtype' => Play::SUBTYPE_OUT_STRIKEOUT,
                            'result' => 'Ponche ' . ($event['subtype'] ?? ''),
                            'batter_id' => $batterId,
                            'pitcher_id' => $pitcherId,
                            'outs_before' => $outs, 'outs_after' => $outs + 1,
                            'balls' => $balls, 'strikes' => 2,
                            'bases_before' => $bases, 'bases_after' => $bases,
                            'meta' => ['pitch_type' => $event['subtype'] ?? null],
                        ]);
                        $outs++;
                        [$batterId, $bases, $half, $inning, $outs, $endHalf, $pitcherId] =
                            $this->advanceBatter($game, $bases, $outs, $half, $inning, $batterId, $pitcherId);
                        $balls = 0;
                        $strikes = 0;
                    } else {
                        $createdPlays[] = $this->recordPlay($game, [
                            'inning' => $inning, 'half' => $half,
                            'type' => Play::TYPE_PITCH,
                            'subtype' => $event['subtype'] ?? 'looking',
                            'result' => 'Strike ' . ($event['subtype'] ?? ''),
                            'batter_id' => $batterId,
                            'pitcher_id' => $pitcherId,
                            'outs_before' => $outs, 'outs_after' => $outs,
                            'balls' => $balls, 'strikes' => $strikes,
                            'bases_before' => $bases, 'bases_after' => $bases,
                        ]);
                    }
                    break;

                case 'foul':
                    if ($strikes < 2) {
                        $strikes++;
                    }
                    $createdPlays[] = $this->recordPlay($game, [
                        'inning' => $inning, 'half' => $half,
                        'type' => Play::TYPE_PITCH,
                        'subtype' => 'foul',
                        'result' => 'Foul',
                        'batter_id' => $batterId,
                        'pitcher_id' => $pitcherId,
                        'outs_before' => $outs, 'outs_after' => $outs,
                        'balls' => $balls, 'strikes' => $strikes,
                        'bases_before' => $bases, 'bases_after' => $bases,
                    ]);
                    break;

                case 'out':
                    $subtype = $event['subtype'] ?? Play::SUBTYPE_OUT_GROUND;
                    $defensive = $event['defensive_sequence'] ?? [];
                    $createdPlays[] = $this->recordPlay($game, [
                        'inning' => $inning, 'half' => $half,
                        'type' => Play::TYPE_OUT,
                        'subtype' => $subtype,
                        'result' => $this->describeOut($subtype, $defensive),
                        'batter_id' => $batterId,
                        'pitcher_id' => $pitcherId,
                        'outs_before' => $outs, 'outs_after' => $outs + 1,
                        'balls' => $balls, 'strikes' => $strikes,
                        'bases_before' => $bases, 'bases_after' => $bases,
                        'meta' => ['defensive_sequence' => $defensive],
                    ]);
                    $outs++;
                    [$batterId, $bases, $half, $inning, $outs, $endHalf, $pitcherId] =
                        $this->advanceBatter($game, $bases, $outs, $half, $inning, $batterId, $pitcherId);
                    $balls = 0;
                    $strikes = 0;
                    break;

                case 'hit':
                    $hitSubtype = $event['subtype'] ?? Play::SUBTYPE_HIT_SINGLE;
                    $runsScored = 0;
                    $rbi = 0;
                    [$bases, $runsScored, $rbi] = $this->advanceRunnersForHit(
                        $bases, $batterId, $hitSubtype
                    );
                    $hitDescription = $this->describeHit($hitSubtype, $runsScored);
                    $createdPlays[] = $this->recordPlay($game, [
                        'inning' => $inning, 'half' => $half,
                        'type' => Play::TYPE_HIT,
                        'subtype' => $hitSubtype,
                        'result' => $hitDescription,
                        'batter_id' => $batterId,
                        'pitcher_id' => $pitcherId,
                        'outs_before' => $outs, 'outs_after' => $outs,
                        'balls' => $balls, 'strikes' => $strikes,
                        'bases_before' => $state['bases'] ?? ['first' => null, 'second' => null, 'third' => null],
                        'bases_after' => $bases,
                        'runs_scored' => $runsScored,
                        'rbi' => $rbi,
                    ]);
                    // El bateador SIEMPRE cambia despues de un hit.
                    [$batterId, $bases, $half, $inning, $outs, $endHalf, $pitcherId] =
                        $this->advanceBatter($game, $bases, $outs, $half, $inning, $batterId, $pitcherId);
                    $balls = 0;
                    $strikes = 0;
                    break;

                case 'balk':
                    // Pitcheo ilegal del pitcher: TODOS los corredores avanzan 1 base.
                    // Si hay alguien en 3B, anota. El bateador NO cambia.
                    [$bases, $balkRuns] = $this->advanceRunnersForBalk($bases);
                    $createdPlays[] = $this->recordPlay($game, [
                        'inning' => $inning, 'half' => $half,
                        'type' => Play::TYPE_BALK,
                        'subtype' => null,
                        'result' => $balkRuns > 0
                            ? "Balk — corredores avanzan ({$balkRuns} carrera" . ($balkRuns !== 1 ? 's' : '') . ')'
                            : 'Balk — corredores avanzan',
                        'batter_id' => $batterId,
                        'pitcher_id' => $pitcherId,
                        'outs_before' => $outs, 'outs_after' => $outs,
                        'balls' => $balls, 'strikes' => $strikes,
                        'bases_before' => $state['bases'] ?? ['first' => null, 'second' => null, 'third' => null],
                        'bases_after' => $bases,
                        'runs_scored' => $balkRuns,
                        'rbi' => 0,
                    ]);
                    break;

                case 'bunt':
                    // Toque de bolas (sacrifice bunt): el bateador hace OUT, los
                    // corredores avanzan una base. Si subtype es 'bunt_single',
                    // el bateador llega a 1B en vez de out.
                    $buntSubtype = $event['subtype'] ?? Play::SUBTYPE_BUNT_SACRIFICE;
                    $isBuntOut = $buntSubtype !== Play::SUBTYPE_BUNT_SINGLE;
                    $runsScored = 0;
                    $rbi = 0;
                    [$bases, $runsScored] = $this->advanceRunnersForBunt($bases, $batterId, $isBuntOut);
                    $rbi = $runsScored;
                    if ($isBuntOut) {
                        $createdPlays[] = $this->recordPlay($game, [
                            'inning' => $inning, 'half' => $half,
                            'type' => Play::TYPE_BUNT,
                            'subtype' => $buntSubtype,
                            'result' => 'Toque de sacrificio' . ($runsScored > 0 ? " ({$runsScored} carrera" . ($runsScored !== 1 ? 's' : '') . ')' : ''),
                            'batter_id' => $batterId,
                            'pitcher_id' => $pitcherId,
                            'outs_before' => $outs, 'outs_after' => $outs + 1,
                            'balls' => $balls, 'strikes' => $strikes,
                            'bases_before' => $state['bases'] ?? ['first' => null, 'second' => null, 'third' => null],
                            'bases_after' => $bases,
                            'runs_scored' => $runsScored,
                            'rbi' => $rbi,
                        ]);
                        $outs++;
                        [$batterId, $bases, $half, $inning, $outs, $endHalf, $pitcherId] =
                            $this->advanceBatter($game, $bases, $outs, $half, $inning, $batterId, $pitcherId);
                        $balls = 0;
                        $strikes = 0;
                    } else {
                        $createdPlays[] = $this->recordPlay($game, [
                            'inning' => $inning, 'half' => $half,
                            'type' => Play::TYPE_BUNT,
                            'subtype' => $buntSubtype,
                            'result' => 'Toque y alcanza 1B' . ($runsScored > 0 ? " ({$runsScored} carrera" . ($runsScored !== 1 ? 's' : '') . ')' : ''),
                            'batter_id' => $batterId,
                            'pitcher_id' => $pitcherId,
                            'outs_before' => $outs, 'outs_after' => $outs,
                            'balls' => $balls, 'strikes' => $strikes,
                            'bases_before' => $state['bases'] ?? ['first' => null, 'second' => null, 'third' => null],
                            'bases_after' => $bases,
                            'runs_scored' => $runsScored,
                            'rbi' => $rbi,
                        ]);
                        [$batterId, $bases, $half, $inning, $outs, $endHalf, $pitcherId] =
                            $this->advanceBatter($game, $bases, $outs, $half, $inning, $batterId, $pitcherId);
                        $balls = 0;
                        $strikes = 0;
                    }
                    break;

                default:
                    throw new \InvalidArgumentException("Tipo de pitcheo no soportado: {$event['type']}");
            }

            if ($endHalf) {
                $createdPlays[] = $this->recordPlay($game, [
                    'inning' => $inning, 'half' => $half,
                    'type' => Play::TYPE_INNING_END,
                    'result' => 'Fin del inning ' . $inning . ' ' . $half,
                    'batter_id' => null,
                    'pitcher_id' => $pitcherId,
                    'outs_before' => $outs, 'outs_after' => $outs,
                    'balls' => 0, 'strikes' => 0,
                    'bases_before' => $bases, 'bases_after' => ['first' => null, 'second' => null, 'third' => null],
                ]);

                // DISI-25: actualizar el modelo Game con el inning/half nuevo.
                // Antes esto no se hacia en processPitch (solo endInning lo hacia),
                // lo que dejaba Game.current_inning / inning_half apuntando al
                // medio inning VIEJO. currentState() consulta Game cuando la ultima
                // jugada es inning_end/game_end, asi que sin esta actualizacion
                // el scoreboard mostraba el inning anterior y el nuevo bateador
                // nunca aparecia (currentState devuelve batter_id=null si Game
                // no esta alineado).
                $isGameOver = $this->isGameOver($game, $inning, $half);
                if ($isGameOver) {
                    $game->update(['status' => 'finalized']);
                } else {
                    $game->update([
                        'current_inning' => $inning,
                        'inning_half' => $half,
                    ]);
                }
            }

            // IMPORTANTE: cuando hubo cambio de bateador (walk, strikeout, out)
            // pero el medio inning continua, registramos una jugada adicional
            // de tipo 'at_bat_start' con el NUEVO bateador y count 0-0.
            // Asi, el siguiente currentState() leera esta jugada y sabra
            // quien esta al bate. Sin esto, currentState() leeria la jugada
            // walk/strikeout/out (cuyo batter_id es el bateador que SALIO) y
            // devolveria el bateador equivocado.
            //
            // DISI-25: tambien se graba cuando hubo endHalf (cambio de inning),
            // para que el nuevo bateador del siguiente medio inning quede
            // persistido. Antes se excluia con `&& ! $endHalf`, lo que dejaba
            // current_batter_id = null justo despues del cambio de inning.
            $hadBatterChange = in_array($event['type'], ['ball', 'strike', 'out', 'hit'], true)
                && $batterId !== null
                && (($event['type'] === 'ball' && $isWalk)
                    || ($event['type'] === 'strike' && $isStrikeout)
                    || $event['type'] === 'out'
                    || $event['type'] === 'hit');
            if ($hadBatterChange) {
                $createdPlays[] = $this->recordPlay($game, [
                    'inning' => $inning, 'half' => $half,
                    'type' => Play::TYPE_PITCH,
                    'subtype' => 'at_bat_start',
                    'result' => $endHalf ? 'Nuevo inning - bateador al bate' : 'Nuevo bateador al bate',
                    'batter_id' => $batterId,
                    'pitcher_id' => $pitcherId,
                    'outs_before' => $outs, 'outs_after' => $outs,
                    'balls' => 0, 'strikes' => 0,
                    'bases_before' => $bases, 'bases_after' => $bases,
                ]);
            }

            // State computado: refleja el estado DESPUES de aplicar el evento
            // (con reset de count, avance de bateador, etc.).
            $newState = [
                'inning' => $inning,
                'half' => $half,
                'outs' => $outs,
                'balls' => $balls,
                'strikes' => $strikes,
                'bases' => $bases,
                'current_batter_id' => $batterId,
                'current_pitcher_id' => $pitcherId,
                'last_play_id' => end($createdPlays)?->id,
                'is_inning_over' => $endHalf,
                'is_game_over' => $this->isGameOver($game, $inning, $half),
            ];

            // DISI-50: persistir el state actualizado a las columnas snapshot
            // del Game para que la vista publica (`/game/live/{token}`) muestre
            // los valores en tiempo real, no solo el scoreboard Alpine.
            $this->syncGameSnapshot($game);

            return [
                'state' => $newState,
                'plays' => $createdPlays,
                'walk' => $isWalk,
                'strikeout' => $isStrikeout,
                'end_half' => $endHalf,
            ];
        });
    }

    /**
     * Avanza al siguiente bateador del lineup. Si outs==3, cierra el medio inning
     * y cambia a top/bottom; si ademas completa el juego, lo marca como terminado.
     *
     * @return array{0: int|null, 1: array, 2: string, 3: int, 4: int, 5: bool, 6: int|null}
     */
    private function advanceBatter(
        Game $game,
        array $bases,
        int $outs,
        string $half,
        int $inning,
        ?int $currentBatterId,
        ?int $pitcherId,
    ): array {
        if ($outs < 3) {
            $nextBatter = $this->nextBatter($game, $half, $currentBatterId);
            return [$nextBatter, $bases, $half, $inning, $outs, false, $pitcherId];
        }

        // 3 outs: cierra medio inning.
        $newBases = ['first' => null, 'second' => null, 'third' => null];
        $newOuts = 0;

        if ($half === 'top') {
            $newHalf = 'bottom';
            // Cuando termina el top, el HOME batea en el bottom. Por tanto el
            // pitcher del nuevo medio inning es del AWAY (equipo que pichea
            // cuando el HOME esta al bate).
            $newPitcher = $this->pitcherFor($game, $game->away_team_id);
            // Si es el inning 1, el home batea por primera vez: arranca desde #1.
            // En innings 2+, el home continua su lineup desde donde se quedo
            // en el bottom del inning anterior.
            if ($inning === 1) {
                $newBatter = $this->firstBatter($game, 'bottom');
            } else {
                $lastHomeBatter = $this->lastBatterForTeamInInning($game, $game->home_team_id, $inning - 1, 'bottom');
                $newBatter = $this->nextBatterByTeam($game, $game->home_team_id, $lastHomeBatter, 'bottom');
            }
            return [$newBatter, $newBases, $newHalf, $inning, $newOuts, true, $newPitcher];
        }

        // bottom: cierra el inning, sube de inning, vuelve a top.
        $newInning = $inning + 1;
        $totalInnings = $game->innings_count ?: 7;

        if ($newInning > $totalInnings) {
            // Fin del juego (se registra una jugada game_end).
            $this->recordPlay($game, [
                'inning' => $inning, 'half' => $half,
                'type' => Play::TYPE_GAME_END,
                'result' => 'Juego terminado',
                'batter_id' => null,
                'pitcher_id' => $pitcherId,
                'outs_before' => $outs, 'outs_after' => $outs,
                'balls' => 0, 'strikes' => 0,
                'bases_before' => $bases, 'bases_after' => $newBases,
            ]);
            return [null, $newBases, 'bottom', $newInning, $newOuts, true, $pitcherId];
        }

        // El away continua su lineup desde donde se quedo en el top del inning
        // que acabamos de cerrar (regla de beisbol: el orden de bateo NO se
        // reinicia entre innings). Si por algun motivo no encontramos al
        // ultimo bateador del away, caemos al #1 como fallback.
        $lastAwayBatter = $this->lastBatterForTeamInInning($game, $game->away_team_id, $inning, 'top');
        $newBatter = $this->nextBatterByTeam($game, $game->away_team_id, $lastAwayBatter, 'top');
        // DISI-25: en el nuevo top el AWAY batea, asi que el HOME pichea.
        // Antes usaba away_team_id (bug), dejando al pitcher equivocado.
        $newPitcher = $this->pitcherFor($game, $game->home_team_id);
        return [$newBatter, $newBases, 'top', $newInning, $newOuts, true, $newPitcher];
    }

    private function isGameOver(Game $game, int $inning, string $half): bool
    {
        $total = $game->innings_count ?: 7;
        return $inning > $total;
    }

    /**
     * Devuelve el primer bateador del lineup para el half actual.
     */
    public function firstBatter(Game $game, string $half): ?int
    {
        $teamId = $half === 'top' ? $game->away_team_id : $game->home_team_id;
        $a = $game->athletes()
            ->wherePivot('team_id', $teamId)
            ->wherePivot('lineup_order', 1)
            ->first();
        return $a?->id;
    }

    /**
     * Devuelve el siguiente bateador en el lineup (lineup_order+1, 1 si pasa de 9).
     */
    public function nextBatter(Game $game, string $half, ?int $currentBatterId): ?int
    {
        $teamId = $half === 'top' ? $game->away_team_id : $game->home_team_id;
        return $this->nextBatterByTeam($game, $teamId, $currentBatterId, $half);
    }

    /**
     * Variante de nextBatter que recibe el teamId explicito (util cuando hay
     * que continuar el lineup de un equipo que NO es el del half actual, por
     * ejemplo en la transicion bottom->top donde el away continua su orden
     * desde el inning anterior).
     */
    public function nextBatterByTeam(Game $game, int $teamId, ?int $currentBatterId, ?string $halfFallback = null): ?int
    {
        if (! $currentBatterId) {
            // Si no hay bateador actual, arrancar desde el #1.
            $a = $game->athletes()
                ->wherePivot('team_id', $teamId)
                ->wherePivot('lineup_order', 1)
                ->first();
            return $a?->id;
        }
        $current = $game->athletes()
            ->wherePivot('team_id', $teamId)
            ->where('athletes.id', $currentBatterId)
            ->first();
        if (! $current) {
            // Bateador no esta en el lineup de este equipo (caso raro): fallback al #1.
            $a = $game->athletes()
                ->wherePivot('team_id', $teamId)
                ->wherePivot('lineup_order', 1)
                ->first();
            return $a?->id;
        }
        $lineupOrder = (int) $current->pivot->lineup_order;
        $next = $game->athletes()
            ->wherePivot('team_id', $teamId)
            ->wherePivot('lineup_order', $lineupOrder === 9 ? 1 : $lineupOrder + 1)
            ->first();
        return $next?->id;
    }

    /**
     * Devuelve el ultimo bateador (con batter_id no nulo) del equipo en el inning dado.
     * Se usa para continuar el lineup entre innings respetando el orden de bateo.
     */
    public function lastBatterForTeamInInning(Game $game, int $teamId, int $inning, string $half): ?int
    {
        return Play::where('game_id', $game->id)
            ->where('inning', $inning)
            ->where('half', $half)
            ->whereNotNull('batter_id')
            ->orderByDesc('sequence')
            ->value('batter_id');
    }

    /**
     * Balk: todos los corredores en base avanzan una base. Si hay alguien en
     * 3B anota. Devuelve [bases_nuevas, runs_anotados].
     */
    public function advanceRunnersForBalk(array $bases): array
    {
        $runs = 0;
        $newBases = ['first' => null, 'second' => null, 'third' => null];
        // 3B -> Home (anota)
        if (! empty($bases['third'])) {
            $runs++;
        } else {
            $newBases['third'] = $bases['third'];
        }
        // 2B -> 3B
        $newBases['third'] = $bases['second'] ?? $newBases['third'];
        // 1B -> 2B
        $newBases['second'] = $bases['first'] ?? null;
        return [$newBases, $runs];
    }

    /**
     * Bunt (toque de sacrificio): el bateador hace OUT, los corredores en base
     * avanzan una base. Si $buntOut es false, el bateador llega a 1B.
     * Devuelve [bases_nuevas, runs_anotados].
     */
    public function advanceRunnersForBunt(array $bases, ?int $batterId, bool $buntOut = true): array
    {
        $runs = 0;
        $newBases = ['first' => null, 'second' => null, 'third' => null];
        // 3B -> Home (anota)
        if (! empty($bases['third'])) {
            $runs++;
        } else {
            $newBases['third'] = $bases['third'];
        }
        // 2B -> 3B
        $newBases['third'] = $bases['second'] ?? $newBases['third'];
        // 1B -> 2B
        $newBases['second'] = $bases['first'] ?? null;
        // Bateador -> 1B (solo si NO es out). DISI-27: usar placeholder si no hay bateador.
        if (! $buntOut) {
            $newBases['first'] = Play::resolveBase($batterId);
        }
        return [$newBases, $runs];
    }

    /**
     * Devuelve el pitcher (is_pitcher=true) del team.
     */
    public function pitcherFor(Game $game, int $teamId): ?int
    {
        $a = $game->athletes()
            ->wherePivot('team_id', $teamId)
            ->wherePivot('is_pitcher', true)
            ->first();
        return $a?->id;
    }

    /**
     * Aplica la logica de bases por bolas (walk) con avance obligatorio:
     *   - Bateador SIEMPRE va a 1B.
     *   - Si 1B estaba ocupado, ese corredor avanza a 2B (forzado).
     *   - Si 2B estaba ocupado, ese corredor avanza a 3B (forzado).
     *   - Si 3B estaba ocupado, ese corredor anota 1 carrera.
     *
     * Devuelve [bases_nuevas, runs_anotados].
     *
     * @return array{0: array, 1: int}
     */
    private function forceRunnersOnWalk(array $bases, ?int $batterId): array
    {
        $runs = 0;
        // Inicializamos preservando los corredores de 2da y 3ra (no se mueven
        // si no estan forzados). Antes solo se copiaban si 1ra estaba ocupada,
        // lo que BORRABA a los corredores de 2da/3ra en un walk con 1ra vacia.
        $new = [
            'first' => null,
            'second' => $bases['second'] ?? null,
            'third' => $bases['third'] ?? null,
        ];

        // Bateador SIEMPRE va a 1B. DISI-27: usar placeholder si no hay bateador.
        $new['first'] = Play::resolveBase($batterId);

        // Avance obligatorio de los corredores existentes
        if (! empty($bases['first'])) {
            // El viejo corredor de 1B avanza a 2B
            $new['second'] = $bases['first'];

            if (! empty($bases['second'])) {
                // El viejo corredor de 2B avanza a 3B
                $new['third'] = $bases['second'];

                if (! empty($bases['third'])) {
                    // El viejo corredor de 3B anota
                    $runs++;
                }
            }
        }

        return [$new, $runs];
    }

    /**
     * Avanza los corredores en base segun el tipo de hit.
     * Reglas (Fase 3 - simples):
     *  - single:  bateador a 1B, corredores avanzan 1 base (3B anota).
     *  - double:  bateador a 2B, corredores avanzan 2 bases (2B y 3B anotan).
     *  - triple:  bateador a 3B, corredores avanzan 3 bases (todos anotan).
     *  - hr:      bateador anota, todos los corredores anotan.
     *  - inside_park (HR de pierna): bateador anota (1 carrera).
     *
     * @return array{0: array, 1: int, 2: int} bases finales, runs scored, rbi
     */
    private function advanceRunnersForHit(array $bases, ?int $batterId, string $hitSubtype): array
    {
        $runs = 0;
        $rbi = 0;
        $newBases = ['first' => null, 'second' => null, 'third' => null];
        $r2 = $bases['second'];
        $r3 = $bases['third'];

        switch ($hitSubtype) {
            case Play::SUBTYPE_HIT_SINGLE:
                // 3B anota, 2B->3B, 1B->2B, bateador->1B.
                if ($r3) { $runs++; $rbi++; }
                $newBases['third'] = $r2;
                $newBases['second'] = $bases['first'];
                // DISI-27: usar placeholder si el bateador no esta identificado.
                $newBases['first'] = Play::resolveBase($batterId);
                break;

            case Play::SUBTYPE_HIT_DOUBLE:
                // 3B anota, 2B anota, 1B->3B, bateador->2B.
                if ($r3) { $runs++; $rbi++; }
                if ($r2) { $runs++; $rbi++; }
                $newBases['third'] = $bases['first'];
                // DISI-27: usar placeholder si el bateador no esta identificado.
                $newBases['second'] = Play::resolveBase($batterId);
                // 1B se mantiene vacia (los corredores ya anotaron o se movieron).
                break;

            case Play::SUBTYPE_HIT_TRIPLE:
                // Todos los corredores anotan, bateador->3B.
                if ($r3) { $runs++; $rbi++; }
                if ($r2) { $runs++; $rbi++; }
                if ($bases['first']) { $runs++; $rbi++; }
                // DISI-27: usar placeholder si el bateador no esta identificado.
                $newBases['third'] = Play::resolveBase($batterId);
                break;

            case Play::SUBTYPE_HIT_HR:
                // Todos los corredores + bateador anotan.
                if ($bases['first']) { $runs++; $rbi++; }
                if ($r2) { $runs++; $rbi++; }
                if ($r3) { $runs++; $rbi++; }
                $runs++; // Bateador anota.
                $rbi++; // RBI del bateador.
                // Bases vacias.
                break;

            case Play::SUBTYPE_HIT_INSIDE_PARK:
                // HR de pierna: solo el bateador anota (1 carrera).
                $runs++;
                $rbi++;
                break;

            default:
                // Hit desconocido: tratar como single.
                if ($r3) { $runs++; $rbi++; }
                $newBases['third'] = $r2;
                $newBases['second'] = $bases['first'];
                // DISI-27: usar placeholder si el bateador no esta identificado.
                $newBases['first'] = Play::resolveBase($batterId);
                break;
        }

        return [$newBases, $runs, $rbi];
    }

    private function describeHit(string $subtype, int $runs): string
    {
        $names = [
            Play::SUBTYPE_HIT_SINGLE => 'Sencillo',
            Play::SUBTYPE_HIT_DOUBLE => 'Doble',
            Play::SUBTYPE_HIT_TRIPLE => 'Triple',
            Play::SUBTYPE_HIT_HR => 'Home Run',
            Play::SUBTYPE_HIT_INSIDE_PARK => 'Home Run de pierna',
        ];
        $name = $names[$subtype] ?? 'Hit';
        if ($runs > 0) {
            return $name . ' (+' . $runs . ' carrera' . ($runs > 1 ? 's' : '') . ')';
        }
        return $name;
    }

    private function basesWithout(array $bases, string $key): array
    {
        $bases[$key] = null;
        return $bases;
    }

    private function describeOut(string $subtype, array $defensive): string
    {
        $map = [
            Play::SUBTYPE_OUT_FLY => 'Flyout',
            Play::SUBTYPE_OUT_LINE => 'Lineout',
            Play::SUBTYPE_OUT_GROUND => 'Roletazo',
            Play::SUBTYPE_OUT_STRIKEOUT => 'Ponche',
            Play::SUBTYPE_OUT_FORCE => 'Forzado',
            Play::SUBTYPE_OUT_TAG => 'Tag',
            Play::SUBTYPE_OUT_POPUP => 'Popup',
        ];
        $name = $map[$subtype] ?? 'Out';
        if ($defensive) {
            return $name . ' (' . implode('-', array_map('strtoupper', $defensive)) . ')';
        }
        return $name;
    }

    private function recordPlay(Game $game, array $data): Play
    {
        $sequence = Play::where('game_id', $game->id)
            ->where('inning', $data['inning'])
            ->where('half', $data['half'])
            ->max('sequence') + 1;
        return Play::create(array_merge($data, [
            'game_id' => $game->id,
            'sequence' => $sequence,
            'recorded_by' => auth()->id(),
            'recorded_at' => now(),
        ]));
    }

    /**
     * Finaliza la media entrada actual manualmente (util para el caso en
     * que el anotador quiere cerrar el inning antes de los 3 outs, por
     * ejemplo en juegos shortened o por lluvia).
     */
    public function endInning(Game $game): array
    {
        $state = Play::currentState($game->id);
        $inning = $state['inning'];
        $half = $state['half'];
        $outs = $state['outs'];
        $bases = $state['bases'];

        // Si ya esta cerrado, no hace nada
        $last = Play::where('game_id', $game->id)->orderByDesc('id')->first();
        if ($last && in_array($last->type, [Play::TYPE_INNING_END, Play::TYPE_GAME_END], true)) {
            return ['status' => 'already_closed'];
        }

        $pitcherId = Play::currentState($game->id)['current_pitcher_id'] ?? $this->pitcherFor($game, $half === 'top' ? $game->away_team_id : $game->home_team_id);

        // Grabar jugada inning_end
        $this->recordPlay($game, [
            'inning' => $inning, 'half' => $half,
            'type' => Play::TYPE_INNING_END,
            'subtype' => 'manual',
            'result' => 'Fin del inning ' . $inning . ' ' . $half . ' (manual)',
            'batter_id' => null,
            'pitcher_id' => $pitcherId,
            'outs_before' => $outs, 'outs_after' => $outs,
            'balls' => 0, 'strikes' => 0,
            'bases_before' => $bases, 'bases_after' => ['first' => null, 'second' => null, 'third' => null],
        ]);

        // Determinar siguiente media entrada
        if ($half === 'top') {
            $newHalf = 'bottom';
            $newInning = $inning;
            // Mismo fix que en advanceBatter: el pitcher del bottom es del AWAY.
            $newPitcher = $this->pitcherFor($game, $game->away_team_id);
            if ($inning === 1) {
                $newBatter = $this->firstBatter($game, 'bottom');
            } else {
                $lastHomeBatter = $this->lastBatterForTeamInInning($game, $game->home_team_id, $inning - 1, 'bottom');
                $newBatter = $this->nextBatterByTeam($game, $game->home_team_id, $lastHomeBatter, 'bottom');
            }
        } else {
            $newHalf = 'top';
            $newInning = $inning + 1;
            // DISI-25: en el nuevo top el AWAY batea, asi que el HOME pichea.
            $newPitcher = $this->pitcherFor($game, $game->home_team_id);
            $lastAwayBatter = $this->lastBatterForTeamInInning($game, $game->away_team_id, $inning, 'top');
            $newBatter = $this->nextBatterByTeam($game, $game->away_team_id, $lastAwayBatter, 'top');
        }

        // Si pasamos del total de innings, fin del juego
        $totalInnings = $game->innings_count ?: 7;
        if ($newInning > $totalInnings) {
            $this->recordPlay($game, [
                'inning' => $inning, 'half' => $half,
                'type' => Play::TYPE_GAME_END,
                'subtype' => 'final_inning',
                'result' => 'Juego terminado (final del inning ' . $totalInnings . ')',
                'batter_id' => null,
                'pitcher_id' => $newPitcher,
                'outs_before' => 0, 'outs_after' => 0,
                'balls' => 0, 'strikes' => 0,
                'bases_before' => ['first' => null, 'second' => null, 'third' => null],
                'bases_after' => ['first' => null, 'second' => null, 'third' => null],
            ]);
            $game->update(['status' => 'finalized']);
            // DISI-50: sincronizar score (carreras) y demas columnas snapshot.
            $this->syncGameSnapshot($game);
            return [
                'status' => 'game_over',
                'inning' => $inning,
                'half' => $half,
                'away_score' => $game->fresh()->away_score,
                'home_score' => $game->fresh()->home_score,
            ];
        }

        // DISI-50: en vez de solo actualizar inning/half, sincronizar TODAS
        // las columnas snapshot del Game con el state real (que despues de
        // inning_end tiene balls=0, strikes=0, outs=0, bases vacias).
        $this->syncGameSnapshot($game);

        return [
            'status' => 'inning_closed',
            'inning' => $newInning,
            'half' => $newHalf,
            'batter_id' => $newBatter,
            'pitcher_id' => $newPitcher,
        ];
    }

    /**
     * Finaliza el juego manualmente (util cuando el anotador quiere cerrar
     * antes del inning final, por ejemplo por mercy rule, lluvia, etc.).
     */
    public function endGame(Game $game): array
    {
        $state = Play::currentState($game->id);
        $inning = $state['inning'];
        $half = $state['half'];
        $outs = $state['outs'];
        $bases = $state['bases'];
        $pitcherId = $state['current_pitcher_id'];

        $this->recordPlay($game, [
            'inning' => $inning, 'half' => $half,
            'type' => Play::TYPE_GAME_END,
            'subtype' => 'manual',
            'result' => 'Juego terminado (manual)',
            'batter_id' => null,
            'pitcher_id' => $pitcherId,
            'outs_before' => $outs, 'outs_after' => $outs,
            'balls' => 0, 'strikes' => 0,
            'bases_before' => $bases, 'bases_after' => $bases,
        ]);
        $game->update(['status' => 'finalized']);
        // DISI-50: sincronizar score + state para que la vista publica muestre
        // las carreras finales y el inning/half donde cerro el juego.
        $this->syncGameSnapshot($game);

        $g = $game->fresh();
        return [
            'status' => 'game_over',
            'inning' => $inning,
            'half' => $half,
            'away_score' => $g->away_score,
            'home_score' => $g->home_score,
        ];
    }

    /**
     * Resumen del inning actual: carreras anotadas y jugadas del inning.
     */
    public function inningSummary(Game $game, int $inning, string $half): array
    {
        $plays = Play::where('game_id', $game->id)
            ->where('inning', $inning)
            ->where('half', $half)
            ->orderBy('sequence')
            ->get();
        $runs = $plays->sum('runs_scored');
        $hits = $plays->where('type', Play::TYPE_HIT)->count();
        $errors = $plays->where('type', Play::TYPE_ERROR)->count();
        $walks = $plays->where('type', Play::TYPE_WALK)->count();
        $strikeouts = $plays->where('type', Play::TYPE_OUT)
            ->where('subtype', Play::SUBTYPE_OUT_STRIKEOUT)->count();
        return [
            'inning' => $inning,
            'half' => $half,
            'plays' => $plays->count(),
            'runs' => $runs,
            'hits' => $hits,
            'errors' => $errors,
            'walks' => $walks,
            'strikeouts' => $strikeouts,
        ];
    }

    /**
     * Accion del anotador sobre un corredor en base (DISI-20).
     *
     * $base:   'first' | 'second' | 'third' (base donde esta el corredor)
     * $action: 'advance' | 'stolen_base' | 'wild_pitch' | 'passed_ball'
     *          | 'error_advance' | 'obstruction' (corredor AVANZA una base;
     *          desde 3B anota carrera)
     *          | 'score_rbi' | 'score_no_rbi' (corredor ANOTA, con o sin RBI)
     *          | 'caught_stealing' | 'pickoff' | 'out_at_2b' | 'out_at_3b'
     *          (corredor OUT — suma 1 out; con 3 outs cierra el medio inning)
     *
     * Restricciones:
     *  - Debe haber un corredor identificado en $base (bases[$base] !== null).
     *  - outs < 3. Si outs >= 3 el caller debe cerrar el inning primero.
     *
     * El bateador NO cambia (es una accion entre pitcheos). Si el out lleva
     * a 3 outs, se cierra el medio inning via advanceBatter (mismo patron
     * que processPitch para strikeout/groundout).
     */
    public function runnerAction(Game $game, string $base, string $action): array
    {
        return DB::transaction(function () use ($game, $base, $action) {
            if (! in_array($base, ['first', 'second', 'third'], true)) {
                throw new \InvalidArgumentException("Base invalida: {$base}");
            }

            $state = Play::currentState($game->id);
            $inning = (int) $state['inning'];
            $half = $state['half'];
            $outs = (int) $state['outs'];
            $balls = (int) $state['balls'];
            $strikes = (int) $state['strikes'];
            $bases = $state['bases'] ?? ['first' => null, 'second' => null, 'third' => null];
            $runnerId = $bases[$base] ?? null;
            $currentBatterId = $state['current_batter_id'];
            $pitcherId = $state['current_pitcher_id'];

            if (! $runnerId) {
                throw new \InvalidArgumentException("No hay corredor identificado en {$base}.");
            }
            if ($outs >= 3) {
                throw new \InvalidArgumentException("No se pueden modificar corredores con 3 outs. Cierra el inning primero.");
            }

            // Obtener el nombre del corredor para los mensajes y la jugada
            $runnerName = $game->athletes()
                ->where('athletes.id', $runnerId)
                ->first()?->full_name ?? "Corredor #{$runnerId}";
            $runnerNumber = $game->athletes()
                ->where('athletes.id', $runnerId)
                ->first()?->number;

            $baseLabel = ['first' => '1B', 'second' => '2B', 'third' => '3B'][$base];

            $runsScored = 0;
            $rbi = 0;
            $newBases = $bases;
            $newOuts = $outs;
            $type = null;
            $subtype = null;
            $result = '';

            switch ($action) {
                // ----- Acciones que AVANZAN al corredor una base -----
                case 'advance':
                case 'stolen_base':
                case 'wild_pitch':
                case 'passed_ball':
                case 'error_advance':
                case 'obstruction':
                    $newBases = $this->moveRunnerOneBase($bases, $base);
                    // Si el corredor estaba en 3B y avanza, anota carrera.
                    if ($base === 'third') {
                        $runsScored = 1;
                        // Wild pitch y passed ball NO son RBI (cobra el pitcher).
                        $rbi = in_array($action, ['wild_pitch', 'passed_ball', 'obstruction'], true) ? 0 : 1;
                    }
                    $type = Play::TYPE_RUNNER_MOVEMENT;
                    $subtype = match ($action) {
                        'advance' => Play::SUBTYPE_RUNNER_ADVANCE,
                        'stolen_base' => Play::SUBTYPE_RUNNER_STOLEN_BASE,
                        'wild_pitch' => Play::SUBTYPE_RUNNER_WILD_PITCH,
                        'passed_ball' => Play::SUBTYPE_RUNNER_PASSED_BALL,
                        'error_advance' => Play::SUBTYPE_RUNNER_ERROR_ADVANCE,
                        'obstruction' => Play::SUBTYPE_RUNNER_OBSTRUCTION,
                    };
                    $result = $this->describeRunnerAction($action, $baseLabel, $runnerName, $runsScored);
                    break;

                // ----- Acciones donde el corredor ANOTA -----
                case 'score_rbi':
                case 'score_no_rbi':
                    if ($base === 'third') {
                        throw new \InvalidArgumentException("Para anotar desde 3B usa 'advance' (anota automatico).");
                    }
                    $newBases = $this->clearBase($bases, $base);
                    $runsScored = 1;
                    $rbi = $action === 'score_rbi' ? 1 : 0;
                    $type = Play::TYPE_RUNNER_MOVEMENT;
                    $subtype = $action === 'score_rbi'
                        ? Play::SUBTYPE_RUNNER_SCORE
                        : Play::SUBTYPE_RUNNER_SCORE_NO_RBI;
                    $result = $this->describeRunnerAction($action, $baseLabel, $runnerName, 1);
                    break;

                // ----- Acciones donde el corredor es OUT -----
                case 'caught_stealing':
                case 'pickoff':
                case 'out_at_2b':
                case 'out_at_3b':
                    $newBases = $this->clearBase($bases, $base);
                    $newOuts = $outs + 1;
                    $type = Play::TYPE_OUT;
                    $subtype = match ($action) {
                        'caught_stealing' => Play::SUBTYPE_OUT_CAUGHT_STEALING,
                        'pickoff' => Play::SUBTYPE_OUT_PICKOFF,
                        'out_at_2b' => Play::SUBTYPE_OUT_AT_2B,
                        'out_at_3b' => Play::SUBTYPE_OUT_AT_3B,
                    };
                    $result = $this->describeRunnerOut($subtype, $baseLabel, $runnerName);
                    break;

                default:
                    throw new \InvalidArgumentException("Accion de corredor no soportada: {$action}");
            }

            $created = [];
            $created[] = $this->recordPlay($game, [
                'inning' => $inning, 'half' => $half,
                'type' => $type,
                'subtype' => $subtype,
                'result' => $result,
                'batter_id' => $currentBatterId, // el bateador no cambia
                'pitcher_id' => $pitcherId,
                'outs_before' => $outs, 'outs_after' => $newOuts,
                'balls' => $balls, 'strikes' => $strikes,
                'bases_before' => $bases, 'bases_after' => $newBases,
                'runs_scored' => $runsScored,
                'rbi' => $rbi,
                'meta' => [
                    'runner_id' => $runnerId,
                    'runner_name' => $runnerName,
                    'runner_number' => $runnerNumber,
                    'from_base' => $base,
                    'action' => $action,
                ],
            ]);

            // Si la accion llevo a 3 outs, cerrar el medio inning.
            $endHalf = $newOuts >= 3;
            if ($endHalf) {
                $created[] = $this->recordPlay($game, [
                    'inning' => $inning, 'half' => $half,
                    'type' => Play::TYPE_INNING_END,
                    'result' => "Fin del inning {$inning} {$half} (out de corredor)",
                    'batter_id' => null,
                    'pitcher_id' => $pitcherId,
                    'outs_before' => $newOuts, 'outs_after' => $newOuts,
                    'balls' => 0, 'strikes' => 0,
                    'bases_before' => $newBases,
                    'bases_after' => ['first' => null, 'second' => null, 'third' => null],
                ]);
                // Actualizar el state del juego: cambiar half/inning
                [$nextBatterId, $newBasesAfterEnd, $newHalf, $newInning, $newOuts, $endHalfActual, $newPitcherId] =
                    $this->advanceBatter($game, $newBases, $newOuts, $half, $inning, $currentBatterId, $pitcherId);
                // Si advanceBatter registro game_end, endHalfActual viene true pero
                // ya esta guardado en created. Si NO lo registro (caso normal),
                // advanceBatter no guarda inning_end (porque ya lo hicimos nosotros).
                // Asi que sincronizamos el Game con el nuevo half/inning:
                if (($endHalfActual ?? false) && ($newInning ?? 0) > 0) {
                    $game->update([
                        'current_inning' => $newInning,
                        'inning_half' => $newHalf,
                    ]);
                }
            }

            // DISI-50: sincronizar el snapshot del Game (bases, outs, score,
            // inning/half) para que la vista publica vea el cambio de
            // corredores, outs y carreras anotadas en tiempo real.
            $this->syncGameSnapshot($game);

            return [
                'success' => true,
                'action' => $action,
                'base' => $base,
                'bases' => $newBases,
                'outs' => $newOuts,
                'runs_scored' => $runsScored,
                'rbi' => $rbi,
                'end_half' => $endHalf,
                'plays' => $created,
            ];
        });
    }

    /**
     * Mueve al corredor de una base a la siguiente.
     * - third -> null (anota, lo registra el caller)
     * - second -> third
     * - first -> second
     */
    private function moveRunnerOneBase(array $bases, string $fromBase): array
    {
        $runnerId = $bases[$fromBase] ?? null;
        if (! $runnerId) {
            return $bases;
        }
        $new = $bases;
        $new[$fromBase] = null;
        if ($fromBase === 'first') {
            $new['second'] = $runnerId;
        } elseif ($fromBase === 'second') {
            $new['third'] = $runnerId;
        } else {
            // third: ya se desconto al hacer null; el caller sumara la carrera.
        }
        return $new;
    }

    /**
     * Saca al corredor de la base (lo deja en null) sin asignarlo a otra.
     */
    private function clearBase(array $bases, string $base): array
    {
        $new = $bases;
        $new[$base] = null;
        return $new;
    }

    /**
     * Descripcion en espanol de la accion del corredor (para el resultado de la jugada).
     */
    private function describeRunnerAction(string $action, string $baseLabel, string $runnerName, int $runs): string
    {
        $from = $baseLabel;
        $to = match ($baseLabel) {
            '1B' => '2B',
            '2B' => '3B',
            '3B' => 'Home',
        };
        $tail = $runs > 0 ? " (+{$runs} carrera" . ($runs !== 1 ? 's' : '') . ')' : '';
        return match ($action) {
            'advance' => "{$runnerName} avanza de {$from} a {$to}{$tail}",
            'stolen_base' => "{$runnerName} roba {$to}{$tail}",
            'wild_pitch' => "{$runnerName} avanza por Wild Pitch a {$to}{$tail}",
            'passed_ball' => "{$runnerName} avanza por Passed Ball a {$to}{$tail}",
            'error_advance' => "{$runnerName} avanza por error a {$to}{$tail}",
            'obstruction' => "OBS — {$runnerName} avanza a {$to}{$tail}",
            'score_rbi' => "{$runnerName} anota (RBI) desde {$from}",
            'score_no_rbi' => "{$runnerName} anota (sin RBI) desde {$from}",
            default => "{$runnerName} desde {$from}{$tail}",
        };
    }

    /**
     * Sincroniza las columnas snapshot del Game (inning, half, outs, balls,
     * strikes, bases, home_score, away_score) con el estado real derivado
     * de la tabla `plays`. La tabla `plays` es la fuente unica de verdad;
     * las columnas en `games` son denormalizaciones que la vista publica
     * (`/game/live/{token}`) lee directo sin pasar por Play::currentState.
     *
     * Antes de este helper, processPitch y runnerAction dejaban Game.{balls,
     * strikes, outs, bases, home_score, away_score} en sus valores iniciales
     * porque solo escribian en `plays`. Resultado: el scoreboard Alpine se veia
     * "vivo" pero la vista publica mostraba 0-0 siempre. endInning SI
     * actualizaba current_inning/inning_half pero NO reseteaba balls/strikes/
     * outs/bases.
     *
     * Llamar DESPUES de crear/actualizar jugadas dentro de la misma
     * transaccion. Lee `Play::currentState` (que ya ve los plays nuevos
     * porque estan en la misma TX) y `Play::scoreboard` para las carreras.
     */
    private function syncGameSnapshot(Game $game): void
    {
        $state = Play::currentState($game->id);
        $score = Play::scoreboard($game->id);

        $game->update([
            'current_inning' => (int) $state['inning'],
            'inning_half' => $state['half'],
            'outs' => (int) $state['outs'],
            'balls' => (int) $state['balls'],
            'strikes' => (int) $state['strikes'],
            'bases' => $state['bases'] ?? null,
            'home_score' => (int) ($score['home'] ?? 0),
            'away_score' => (int) ($score['away'] ?? 0),
        ]);
    }

    /**
     * Descripcion en espanol del out del corredor (para el resultado de la jugada).
     */
    private function describeRunnerOut(string $subtype, string $baseLabel, string $runnerName): string
    {
        return match ($subtype) {
            Play::SUBTYPE_OUT_CAUGHT_STEALING => "{$runnerName} OUT en intento de robo desde {$baseLabel}",
            Play::SUBTYPE_OUT_PICKOFF => "{$runnerName} OUT por pickoff (viraje) en {$baseLabel}",
            Play::SUBTYPE_OUT_AT_2B => "{$runnerName} OUT en 2B",
            Play::SUBTYPE_OUT_AT_3B => "{$runnerName} OUT en 3B",
            default => "{$runnerName} OUT desde {$baseLabel}",
        };
    }

    /**
     * Registra una sustitucion de pitcher, bateador o pinch runner.
     * $kind: 'pitcher' | 'batter' | 'pr'
     * $outAthleteId: atleta que sale
     * $inAthleteId: atleta que entra
     * $base (solo PR): 'first' | 'second' | 'third' - base del corredor a sustituir
     */
    public function substitute(Game $game, string $kind, int $outAthleteId, int $inAthleteId, ?string $base = null): array
    {
        $state = Play::currentState($game->id);
        $inning = $state['inning'];
        $half = $state['half'];
        $outs = $state['outs'];
        $bases = $state['bases'];
        $currentBatterId = $state['current_batter_id'];
        $currentPitcherId = $state['current_pitcher_id'];

        $teamId = $half === 'top' ? $game->away_team_id : $game->home_team_id;

        // Validar que ambos atletas esten en el roster del juego y del equipo correcto
        $rosterIds = $game->athletes()->wherePivot('team_id', $teamId)->pluck('athletes.id')->toArray();
        if (! in_array($outAthleteId, $rosterIds, true)) {
            throw new \InvalidArgumentException("Atleta saliente no esta en el roster del equipo.");
        }
        if (! in_array($inAthleteId, $rosterIds, true)) {
            throw new \InvalidArgumentException("Atleta entrante no esta en el roster del equipo.");
        }

        $created = [];
        $newBases = $bases;
        $newBatterId = $currentBatterId;
        $newPitcherId = $currentPitcherId;

        switch ($kind) {
            case 'pitcher':
                // Cambio de pitcher: el nuevo pitcher reemplaza al actual.
                // Actualizar pivot: el viejo deja de ser pitcher, el nuevo pasa a serlo.
                DB::table('game_athlete')
                    ->where('game_id', $game->id)
                    ->where('team_id', $teamId)
                    ->where('is_pitcher', true)
                    ->update(['is_pitcher' => false]);
                DB::table('game_athlete')
                    ->where('game_id', $game->id)
                    ->where('athlete_id', $inAthleteId)
                    ->update(['is_pitcher' => true]);

                $outName = $game->athletes()->where('athletes.id', $outAthleteId)->first()?->full_name ?? 'ID ' . $outAthleteId;
                $inName = $game->athletes()->where('athletes.id', $inAthleteId)->first()?->full_name ?? 'ID ' . $inAthleteId;
                $created[] = $this->recordPlay($game, [
                    'inning' => $inning, 'half' => $half,
                    'type' => Play::TYPE_SUBSTITUTION,
                    'subtype' => Play::SUBTYPE_SUB_PITCHER,
                    'result' => "Cambio de pitcher: sale {$outName}, entra {$inName}",
                    'batter_id' => $currentBatterId,
                    'pitcher_id' => $inAthleteId, // el nuevo pitcher
                    'outs_before' => $outs, 'outs_after' => $outs,
                    'balls' => $state['balls'], 'strikes' => $state['strikes'],
                    'bases_before' => $bases, 'bases_after' => $bases,
                    'meta' => ['out_athlete_id' => $outAthleteId, 'in_athlete_id' => $inAthleteId],
                ]);
                $newPitcherId = $inAthleteId;
                break;

            case 'batter':
                // Cambio de bateador: el nuevo bateador reemplaza al actual
                // sin afectar el orden de bateo (sigue contando en el mismo turno).
                $outName = $game->athletes()->where('athletes.id', $outAthleteId)->first()?->full_name ?? 'ID ' . $outAthleteId;
                $inName = $game->athletes()->where('athletes.id', $inAthleteId)->first()?->full_name ?? 'ID ' . $inAthleteId;
                $created[] = $this->recordPlay($game, [
                    'inning' => $inning, 'half' => $half,
                    'type' => Play::TYPE_SUBSTITUTION,
                    'subtype' => Play::SUBTYPE_SUB_BATTER,
                    'result' => "Cambio de bateador: sale {$outName}, entra {$inName}",
                    'batter_id' => $inAthleteId,
                    'pitcher_id' => $currentPitcherId,
                    'outs_before' => $outs, 'outs_after' => $outs,
                    'balls' => $state['balls'], 'strikes' => $state['strikes'],
                    'bases_before' => $bases, 'bases_after' => $bases,
                    'meta' => ['out_athlete_id' => $outAthleteId, 'in_athlete_id' => $inAthleteId],
                ]);
                $newBatterId = $inAthleteId;
                break;

            case 'pr':
                // Pinch runner: un corredor en base es reemplazado por otro atleta.
                if (! $base || ! in_array($base, ['first', 'second', 'third'], true)) {
                    throw new \InvalidArgumentException("Base invalida para PR.");
                }
                if (empty($bases[$base])) {
                    throw new \InvalidArgumentException("No hay corredor en {$base}.");
                }
                $newBases = $bases;
                $newBases[$base] = $inAthleteId;
                $outName = $game->athletes()->where('athletes.id', $outAthleteId)->first()?->full_name ?? 'ID ' . $outAthleteId;
                $inName = $game->athletes()->where('athletes.id', $inAthleteId)->first()?->full_name ?? 'ID ' . $inAthleteId;
                $baseLabel = ['first' => '1B', 'second' => '2B', 'third' => '3B'][$base];
                $created[] = $this->recordPlay($game, [
                    'inning' => $inning, 'half' => $half,
                    'type' => Play::TYPE_SUBSTITUTION,
                    'subtype' => Play::SUBTYPE_SUB_PR,
                    'result' => "Pinch runner en {$baseLabel}: sale {$outName}, entra {$inName}",
                    'batter_id' => $currentBatterId,
                    'pitcher_id' => $currentPitcherId,
                    'outs_before' => $outs, 'outs_after' => $outs,
                    'balls' => $state['balls'], 'strikes' => $state['strikes'],
                    'bases_before' => $bases, 'bases_after' => $newBases,
                    'meta' => ['out_athlete_id' => $outAthleteId, 'in_athlete_id' => $inAthleteId, 'base' => $base],
                ]);
                break;

            default:
                throw new \InvalidArgumentException("Tipo de sustitucion no soportado: {$kind}");
        }

        // DISI-50: sincronizar snapshot (especialmente bases despues de un PR).
        $this->syncGameSnapshot($game);

        return [
            'success' => true,
            'kind' => $kind,
            'bases' => $newBases,
            'batter_id' => $newBatterId,
            'pitcher_id' => $newPitcherId,
            'plays' => $created,
        ];
    }
}

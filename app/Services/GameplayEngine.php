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
                        $bases = $this->forceRunnersOnWalk($bases);
                        $createdPlays[] = $this->recordPlay($game, [
                            'inning' => $inning, 'half' => $half,
                            'type' => Play::TYPE_WALK,
                            'subtype' => 'walk',
                            'result' => 'Base por bolas',
                            'batter_id' => $batterId,
                            'pitcher_id' => $pitcherId,
                            'outs_before' => $outs, 'outs_after' => $outs,
                            'balls' => 3, 'strikes' => $strikes,
                            'bases_before' => $basesBefore, 'bases_after' => $bases,
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
            }

            // IMPORTANTE: cuando hubo cambio de bateador (walk, strikeout, out)
            // pero el medio inning continua, registramos una jugada adicional
            // de tipo 'at_bat_start' con el NUEVO bateador y count 0-0.
            // Asi, el siguiente currentState() leera esta jugada y sabra
            // quien esta al bate. Sin esto, currentState() leeria la jugada
            // walk/strikeout/out (cuyo batter_id es el bateador que SALIO) y
            // devolveria el bateador equivocado.
            $hadBatterChange = in_array($event['type'], ['ball', 'strike', 'out', 'hit'], true)
                && ! $endHalf
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
                    'result' => 'Nuevo bateador al bate',
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
            $newPitcher = $this->pitcherFor($game, $game->home_team_id);
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
        $newPitcher = $this->pitcherFor($game, $game->away_team_id);
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
        // Bateador -> 1B (solo si NO es out)
        if (! $buntOut) {
            $newBases['first'] = $batterId;
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
     * Aplica la logica de bases por bolas: si hay corredor en 1B, se fuerza
     * a 2B, 3B y home. Simplificado: 1B->2B, 2B->3B, 3B->home. El bateador va a 1B.
     */
    private function forceRunnersOnWalk(array $bases): array
    {
        $new = ['first' => null, 'second' => null, 'third' => null];
        // Bateador a 1B
        $new['first'] = $bases['first'];
        // 2B -> 3B si hay
        $new['third'] = $bases['second'];
        // 3B -> home (carrera)
        // En Fase 2 simplificado: no marcamos carrera; el usuario avanzara en Fase 3/4.
        // Pero guardamos el 3B -> home.
        // Para preservar el orden, no usamos el valor viejo de third como first.
        return $new;
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
                $newBases['first'] = $batterId;
                break;

            case Play::SUBTYPE_HIT_DOUBLE:
                // 3B anota, 2B anota, 1B->3B, bateador->2B.
                if ($r3) { $runs++; $rbi++; }
                if ($r2) { $runs++; $rbi++; }
                $newBases['third'] = $bases['first'];
                $newBases['second'] = $batterId;
                // 1B se mantiene vacia (los corredores ya anotaron o se movieron).
                break;

            case Play::SUBTYPE_HIT_TRIPLE:
                // Todos los corredores anotan, bateador->3B.
                if ($r3) { $runs++; $rbi++; }
                if ($r2) { $runs++; $rbi++; }
                if ($bases['first']) { $runs++; $rbi++; }
                $newBases['third'] = $batterId;
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
                $newBases['first'] = $batterId;
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
            $newPitcher = $this->pitcherFor($game, $game->home_team_id);
            if ($inning === 1) {
                $newBatter = $this->firstBatter($game, 'bottom');
            } else {
                $lastHomeBatter = $this->lastBatterForTeamInInning($game, $game->home_team_id, $inning - 1, 'bottom');
                $newBatter = $this->nextBatterByTeam($game, $game->home_team_id, $lastHomeBatter, 'bottom');
            }
        } else {
            $newHalf = 'top';
            $newInning = $inning + 1;
            $newPitcher = $this->pitcherFor($game, $game->away_team_id);
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
            return [
                'status' => 'game_over',
                'inning' => $inning,
                'half' => $half,
                'away_score' => $game->fresh()->away_score,
                'home_score' => $game->fresh()->home_score,
            ];
        }

        $game->update([
            'current_inning' => $newInning,
            'inning_half' => $newHalf,
        ]);

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

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
            $newBatter = $this->firstBatter($game, 'bottom');
            $newPitcher = $this->pitcherFor($game, $game->home_team_id);
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

        $newBatter = $this->firstBatter($game, 'top');
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
        if (! $currentBatterId) {
            return $this->firstBatter($game, $half);
        }
        $current = $game->athletes()
            ->wherePivot('team_id', $teamId)
            ->where('athletes.id', $currentBatterId)
            ->first();
        if (! $current) {
            return $this->firstBatter($game, $half);
        }
        $lineupOrder = (int) $current->pivot->lineup_order;
        $next = $game->athletes()
            ->wherePivot('team_id', $teamId)
            ->wherePivot('lineup_order', $lineupOrder === 9 ? 1 : $lineupOrder + 1)
            ->first();
        return $next?->id ?? $this->firstBatter($game, $half);
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
}

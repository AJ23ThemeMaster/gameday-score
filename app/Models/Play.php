<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Representa UNA jugada (play) dentro de un juego de beisbol.
 *
 * Tipos soportados (columna `type`):
 *  - 'pitch'             : un lanzamiento individual (strike, ball, foul). NO produce cambio en outs/bases.
 *  - 'out'               : un out (groundout, flyout, strikeout, etc.). Suma outs.
 *  - 'hit'               : un hit (single, double, triple, HR, inside-the-park HR).
 *  - 'walk'              : base por bolas (BB).
 *  - 'hbp'               : golpeado por el lanzador.
 *  - 'error'             : error defensivo que permite al bateador llegar a base.
 *  - 'bunt'              : toque de bola (sacrifice, bunt single, bunt out).
 *  - 'balk'              : balk del pitcher (avanza corredores).
 *  - 'runner_movement'  : robo, wild pitch, passed ball (avanza un corredor especifico).
 *  - 'substitution'      : cambio de jugador (lineup o pitcher).
 *  - 'inning_end'        : marca el cierre del inning (para el resumen).
 *  - 'game_end'          : marca el final del juego.
 *
 * El campo `meta` (JSON) guarda datos especificos del tipo.
 *
 * @property int $id
 * @property int $game_id
 * @property int $inning
 * @property string $half 'top' | 'bottom'
 * @property int $sequence
 * @property string $type
 * @property string|null $subtype
 * @property string|null $result
 * @property int|null $batter_id
 * @property int|null $pitcher_id
 * @property int $outs_before
 * @property int $outs_after
 * @property array|null $bases_before
 * @property array|null $bases_after
 * @property int $balls
 * @property int $strikes
 * @property int $runs_scored
 * @property int $rbi
 * @property array|null $runs_detail
 * @property array|null $meta
 * @property int|null $recorded_by
 * @property \Illuminate\Support\Carbon $recorded_at
 * @property-read \App\Models\Game $game
 * @property-read \App\Models\Athlete|null $batter
 * @property-read \App\Models\Athlete|null $pitcher
 */
class Play extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id', 'inning', 'half', 'sequence', 'type', 'subtype', 'result',
        'batter_id', 'pitcher_id',
        'outs_before', 'outs_after', 'bases_before', 'bases_after',
        'balls', 'strikes',
        'runs_scored', 'rbi', 'runs_detail', 'meta',
        'recorded_by', 'recorded_at',
    ];

    protected $casts = [
        'bases_before' => 'array',
        'bases_after' => 'array',
        'runs_detail' => 'array',
        'meta' => 'array',
        'recorded_at' => 'datetime',
    ];

    // Tipos
    public const TYPE_PITCH = 'pitch';
    public const TYPE_OUT = 'out';
    public const TYPE_HIT = 'hit';
    public const TYPE_WALK = 'walk';
    public const TYPE_HBP = 'hbp';
    public const TYPE_ERROR = 'error';
    public const TYPE_BUNT = 'bunt';
    public const TYPE_BALK = 'balk';
    public const TYPE_RUNNER_MOVEMENT = 'runner_movement';
    public const TYPE_SUBSTITUTION = 'substitution';
    public const TYPE_INNING_END = 'inning_end';
    public const TYPE_GAME_END = 'game_end';

    // DISI-27: placeholder para corredores cuyo bateador NO esta identificado
    // (equipo sin roster, atleta eliminado del roster, etc.). El motor usa este
    // string en lugar de null para que el frontend SIEMPRE muestre un indicador
    // en la base (no una base vacia). El frontend detecta cualquier string en
    // una base como "corredor anonimo" y muestra el label "CORREDOR".
    public const ANON_RUNNER = 'corredor';

    /**
     * Devuelve el ID del bateador si existe, o el placeholder ANON_RUNNER.
     * Usar en todas las asignaciones a bases cuando el bateador se embasa.
     */
    public static function resolveBase(?int $batterId): int|string
    {
        return $batterId ?? self::ANON_RUNNER;
    }

    // Subtipos de strike (pitch type='pitch', subtype)
    public const SUBTYPE_STRIKE_LOOKING = 'looking';
    public const SUBTYPE_STRIKE_SWINGING = 'swinging';
    public const SUBTYPE_STRIKE_FOUL_TIP = 'foul_tip';

    // Subtipos de out
    public const SUBTYPE_OUT_FLY = 'fly';
    public const SUBTYPE_OUT_LINE = 'line';
    public const SUBTYPE_OUT_GROUND = 'ground';
    public const SUBTYPE_OUT_STRIKEOUT = 'strikeout';
    public const SUBTYPE_OUT_FORCE = 'force';
    public const SUBTYPE_OUT_TAG = 'tag';
    public const SUBTYPE_OUT_POPUP = 'popup';

    // Subtipos de hit
    public const SUBTYPE_HIT_SINGLE = 'single';
    public const SUBTYPE_HIT_DOUBLE = 'double';
    public const SUBTYPE_HIT_TRIPLE = 'triple';
    public const SUBTYPE_HIT_HR = 'hr';
    public const SUBTYPE_HIT_INSIDE_PARK = 'inside_park';

    // Subtipos de bunt
    public const SUBTYPE_BUNT_SACRIFICE = 'sacrifice';
    public const SUBTYPE_BUNT_SINGLE = 'bunt_single';
    public const SUBTYPE_BUNT_OUT = 'bunt_out';

    // Subtipos de substitution
    public const SUBTYPE_SUB_PITCHER = 'pitcher_change';
    public const SUBTYPE_SUB_BATTER = 'batter_change';
    public const SUBTYPE_SUB_RUNNER = 'runner_change';
    public const SUBTYPE_SUB_PR = 'pr_change'; // pinch runner

    // Subtipos de runner_movement (DISI-20) — acciones del anotador
    // sobre un corredor identificado en una base. NO son resultado del
    // bateador (hit, walk, etc.) sino del propio juego / estrategia.
    public const SUBTYPE_RUNNER_ADVANCE = 'advance';             // Avanza por jugada del juego
    public const SUBTYPE_RUNNER_STOLEN_BASE = 'stolen_base';     // Robo de base (steal)
    public const SUBTYPE_RUNNER_WILD_PITCH = 'wild_pitch';       // Wild pitch (corredor avanza)
    public const SUBTYPE_RUNNER_PASSED_BALL = 'passed_ball';     // Passed ball (corredor avanza)
    public const SUBTYPE_RUNNER_ERROR_ADVANCE = 'error_advance'; // Avanza por error
    public const SUBTYPE_RUNNER_OBSTRUCTION = 'obstruction';     // OBS (obstrucción del corredor)
    public const SUBTYPE_RUNNER_SCORE = 'score_rbi';             // Anota con RBI (1)
    public const SUBTYPE_RUNNER_SCORE_NO_RBI = 'score_no_rbi';   // Anota sin RBI (0)

    // Subtipos de out del corredor (caugth stealing, pickoff, out al intentar avanzar)
    public const SUBTYPE_OUT_CAUGHT_STEALING = 'caught_stealing';
    public const SUBTYPE_OUT_PICKOFF = 'pickoff';
    public const SUBTYPE_OUT_AT_2B = 'out_at_2b';
    public const SUBTYPE_OUT_AT_3B = 'out_at_3b';

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function batter(): BelongsTo
    {
        return $this->belongsTo(Athlete::class, 'batter_id');
    }

    public function pitcher(): BelongsTo
    {
        return $this->belongsTo(Athlete::class, 'pitcher_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Devuelve el estado actual del juego basado en la ultima jugada.
     * Reconstruye: inning, half, outs, bases, current_batter, current_pitcher, count.
     *
     * Importante: las jugadas guardan el count AL MOMENTO del evento.
     * El state actual debe reflejar el count POST-jugada, que se resetea
     * cuando la jugada cambia de bateador (walk, strikeout, out, hit,
     * inning_end, game_end, etc.).
     */
    public static function currentState(int $gameId): array
    {
        $last = static::where('game_id', $gameId)
            ->orderByDesc('inning')
            ->orderByDesc('half')
            ->orderByDesc('sequence')
            ->first();

        if (! $last) {
            return static::initialState($gameId);
        }

        $bases = $last->bases_after ?? ['first' => null, 'second' => null, 'third' => null];

        // Determinar si el count se resetea (cambio de bateador).
        $resetsCount = in_array($last->type, [
            static::TYPE_WALK,
            static::TYPE_HIT,
            static::TYPE_HBP,
            static::TYPE_ERROR,
            static::TYPE_BUNT,
            static::TYPE_BALK,
            static::TYPE_OUT,
            static::TYPE_INNING_END,
            static::TYPE_GAME_END,
        ], true);

        $balls = $resetsCount ? 0 : $last->balls;
        $strikes = $resetsCount ? 0 : $last->strikes;

        // Si la ultima jugada es inning_end o game_end, leer el inning/half
        // actualizado del modelo Game (la jugada queda con el half viejo por
        // diseño, pero el Game ya tiene el siguiente half grabado por
        // endInning/endGame).
        $game = Game::find($gameId);
        $inning = $last->inning;
        $half = $last->half;
        if ($game && in_array($last->type, [static::TYPE_INNING_END, static::TYPE_GAME_END], true)) {
            $inning = $game->current_inning;
            $half = $game->inning_half;
        }

        return [
            'inning' => $inning,
            'half' => $half,
            'outs' => $last->outs_after,
            'balls' => $balls,
            'strikes' => $strikes,
            'bases' => $bases,
            'current_batter_id' => $last->batter_id,
            'current_pitcher_id' => $last->pitcher_id,
            'last_play_id' => $last->id,
            'last_play_type' => $last->type,
            'is_inning_over' => $last->type === static::TYPE_INNING_END,
            'is_game_over' => $last->type === static::TYPE_GAME_END,
        ];
    }

    public static function initialState(int $gameId): array
    {
        $game = Game::find($gameId);
        $homePitcher = $game->athletes()
            ->wherePivot('team_id', $game->home_team_id)
            ->wherePivot('is_pitcher', true)
            ->first();
        $awayPitcher = $game->athletes()
            ->wherePivot('team_id', $game->away_team_id)
            ->wherePivot('is_pitcher', true)
            ->first();

        // DISI-31d: leer el half del modelo Game en vez de hardcodear 'top'.
        // Asi si el juego se crea o se resetea con inning_half='bottom' (caso
        // raro pero valido), el state inicial respeta ese half.
        $defaultHalf = $game?->inning_half ?: 'top';

        return [
            'inning' => 1,
            'half' => $defaultHalf,
            'outs' => 0,
            'balls' => 0,
            'strikes' => 0,
            'bases' => ['first' => null, 'second' => null, 'third' => null],
            'current_batter_id' => null,
            'current_pitcher_id' => $homePitcher?->id, // top: visitante batea, local (home) pichea
            'last_play_id' => null,
            'is_inning_over' => false,
            'is_game_over' => false,
        ];
    }

    /**
     * Calcula el score del juego contando carreras, hits y errores por inning.
     *
     * Devuelve:
     *  - home/away: totales de carreras
     *  - by_inning: [inning => [top => ['R'=>N, 'H'=>N, 'E'=>N], bottom => {...}]]
     *  - totals_hits: totales de hits por equipo (home/away)
     *  - totals_errors: totales de errores por equipo (home/away)
     *
     * Hits: plays.type = 'hit' (single, double, triple, HR, inside-the-park).
     * Errors: plays.type = 'error'.
     */
    public static function scoreboard(int $gameId): array
    {
        // Carreras: solo plays con runs_scored > 0
        $runPlays = static::where('game_id', $gameId)
            ->where('runs_scored', '>', 0)
            ->orderBy('inning')
            ->orderBy('half')
            ->orderBy('sequence')
            ->get();

        // Hits: una jugada por hit (single, double, triple, HR, ITP)
        $hitPlays = static::where('game_id', $gameId)
            ->where('type', static::TYPE_HIT)
            ->orderBy('inning')
            ->orderBy('half')
            ->orderBy('sequence')
            ->get();

        // Errores: una jugada por error
        $errorPlays = static::where('game_id', $gameId)
            ->where('type', static::TYPE_ERROR)
            ->orderBy('inning')
            ->orderBy('half')
            ->orderBy('sequence')
            ->get();

        $byInning = []; // [inning => [half => ['R'=>N, 'H'=>N, 'E'=>N]]]
        $totals = ['home' => 0, 'away' => 0];
        $totalsHits = ['home' => 0, 'away' => 0];
        $totalsErrors = ['home' => 0, 'away' => 0];

        foreach ($runPlays as $p) {
            $byInning[$p->inning][$p->half]['R'] = ($byInning[$p->inning][$p->half]['R'] ?? 0) + $p->runs_scored;
            if ($p->half === 'top') {
                $totals['away'] += $p->runs_scored;
            } else {
                $totals['home'] += $p->runs_scored;
            }
        }

        foreach ($hitPlays as $p) {
            $byInning[$p->inning][$p->half]['H'] = ($byInning[$p->inning][$p->half]['H'] ?? 0) + 1;
            if ($p->half === 'top') {
                $totalsHits['away']++;
            } else {
                $totalsHits['home']++;
            }
        }

        foreach ($errorPlays as $p) {
            $byInning[$p->inning][$p->half]['E'] = ($byInning[$p->inning][$p->half]['E'] ?? 0) + 1;
            // Errors se cuentan al equipo que DEFENDE (no al que batea).
            // top = visitante batea, local DEFENDE => error de HOME
            // bottom = local batea, visitante DEFENDE => error de AWAY
            if ($p->half === 'top') {
                $totalsErrors['home']++;
            } else {
                $totalsErrors['away']++;
            }
        }

        return [
            'home' => $totals['home'],
            'away' => $totals['away'],
            'totals_hits' => $totalsHits,
            'totals_errors' => $totalsErrors,
            'by_inning' => $byInning,
        ];
    }

    /**
     * Estadisticas en vivo del pitcher durante un juego.
     * Calcula: lanzamientos (pitches), strikes, balls, ponches (K),
     * hits permitidos (H), boletos (BB).
     */
    public static function statsForPitcher(int $gameId, int $pitcherId): array
    {
        $plays = static::where('game_id', $gameId)
            ->where('pitcher_id', $pitcherId)
            ->get();

        $pitches = 0;
        $strikes = 0;
        $balls = 0;
        $strikeouts = 0;
        $hits = 0;
        $walks = 0;

        foreach ($plays as $p) {
            $type = $p->type;
            $subtype = $p->subtype;

            if ($type === static::TYPE_PITCH) {
                // 'at_bat_start' marca el inicio del turno de un bateador
                // (despues de walk, strikeout, out). NO es un lanzamiento real
                // y no debe contar en el contador de pitches.
                if ($subtype === 'at_bat_start') {
                    continue;
                }
                $pitches++;
                if ($subtype === 'ball') {
                    $balls++;
                } elseif (in_array($subtype, ['looking', 'swinging', 'foul_tip'], true)) {
                    $strikes++;
                }
                // 'foul' (pitch) no incrementa ni balls ni strikes (cuenta como strike visual pero
                // para el conteo de pitches/strikes lo dejamos en pitches solamente)
            } elseif ($type === static::TYPE_OUT && $subtype === static::SUBTYPE_OUT_STRIKEOUT) {
                $strikeouts++;
            } elseif ($type === static::TYPE_HIT) {
                $hits++;
            } elseif ($type === static::TYPE_WALK) {
                $walks++;
            }
        }

        return [
            'pitches' => $pitches,
            'strikes' => $strikes,
            'balls' => $balls,
            'strikeouts' => $strikeouts,
            'hits' => $hits,
            'walks' => $walks,
        ];
    }

    /**
     * Estadisticas en vivo del bateador durante un juego.
     * Calcula: turnos al bate (AB), hits (H), ponches (K), boletos (BB),
     * promedio (AVG = H/AB).
     */
    public static function statsForBatter(int $gameId, int $batterId): array
    {
        $plays = static::where('game_id', $gameId)
            ->where('batter_id', $batterId)
            ->get();

        $at_bats = 0;
        $hits = 0;
        $strikeouts = 0;
        $walks = 0;

        foreach ($plays as $p) {
            $type = $p->type;
            if ($type === static::TYPE_HIT) {
                $at_bats++;
                $hits++;
            } elseif ($type === static::TYPE_OUT) {
                $at_bats++;
                if ($p->subtype === static::SUBTYPE_OUT_STRIKEOUT) {
                    $strikeouts++;
                }
            } elseif ($type === static::TYPE_WALK) {
                $walks++;
                // Los walks no cuentan como AB.
            }
        }

        $avg = $at_bats > 0 ? round($hits / $at_bats, 3) : 0.0;

        return [
            'at_bats' => $at_bats,
            'hits' => $hits,
            'strikeouts' => $strikeouts,
            'walks' => $walks,
            'avg' => $avg,
        ];
    }

    /**
     * Estadisticas historicas COMPLETAS del juego (box score).
     * Devuelve:
     *  - pitching: por cada pitcher que ha lanzado (pitches, K, BB, H, R, ER aprox)
     *  - batting:  por cada bateador que ha bateado (AB, H, 2B, 3B, HR, RBI, R, BB, K, AVG)
     *  - line_score: carreras por inning de cada equipo
     *
     * No incluye jugadas de tipo 'pitch' con subtype 'at_bat_start' (es un
     * marcador interno, no un lanzamiento real).
     */
    public static function statsAll(int $gameId, int $homeTeamId, int $awayTeamId, int $totalInnings = 7): array
    {
        $plays = static::where('game_id', $gameId)
            ->orderBy('inning')
            ->orderBy('half')
            ->orderBy('sequence')
            ->get();

        // ============ PITCHING ============
        $pitchingById = [];
        foreach ($plays as $p) {
            $pid = $p->pitcher_id;
            if (! $pid) continue;
            if (! isset($pitchingById[$pid])) {
                $pitchingById[$pid] = [
                    'pitcher_id' => $pid,
                    'pitches' => 0,
                    'strikes' => 0,
                    'balls' => 0,
                    'strikeouts' => 0,
                    'hits_allowed' => 0,
                    'walks_allowed' => 0,
                    'runs_allowed' => 0,
                    'batters_faced' => 0,
                ];
            }
            $t = $p->type;
            $st = $p->subtype;
            if ($t === static::TYPE_PITCH) {
                if ($st === 'at_bat_start') {
                    // Marca inicio de turno; NO es un lanzamiento real.
                    $pitchingById[$pid]['batters_faced']++;
                    continue;
                }
                $pitchingById[$pid]['pitches']++;
                if ($st === 'ball') {
                    $pitchingById[$pid]['balls']++;
                } elseif (in_array($st, ['looking', 'swinging', 'foul_tip'], true)) {
                    $pitchingById[$pid]['strikes']++;
                }
            } elseif ($t === static::TYPE_OUT && $st === static::SUBTYPE_OUT_STRIKEOUT) {
                $pitchingById[$pid]['strikeouts']++;
            } elseif ($t === static::TYPE_HIT) {
                $pitchingById[$pid]['hits_allowed']++;
            } elseif ($t === static::TYPE_WALK) {
                $pitchingById[$pid]['walks_allowed']++;
            }
            // Carreras permitidas: las del inning/half se atribuyen al pitcher
            // activo en ese momento (pitcher_id del play).
            $pitchingById[$pid]['runs_allowed'] += (int) $p->runs_scored;
        }

        // Calcular IP (innings pitched) a partir de los outs registrados.
        // outs_after es el total de outs DEL juego en ese momento, no los
        // atribuidos a este pitcher. Para aproximar: contar outs por pitcher
        // requiere un walk, lo dejamos como derivado simple: cada out consume
        // 1/3 de inning. Aqui usamos una mejor aproximacion: contar los
        // outs en plays donde el pitcher es este Y outs_after > outs_before.
        $outsByPitcher = [];
        foreach ($plays as $p) {
            $pid = $p->pitcher_id;
            if (! $pid) continue;
            $t = $p->type;
            $st = $p->subtype;
            $isOut = ($t === static::TYPE_OUT)
                || ($t === static::TYPE_PITCH && in_array($st, ['looking', 'swinging', 'foul_tip'], true) && ((int) $p->strikes >= 2))
                || ($t === static::TYPE_BUNT && in_array($st, ['sacrifice', 'bunt_out'], true));
            // Simplificacion: solo contar outs REALES (type=out) o strikeouts
            // via TYPE_PITCH que terminaron ponche. El resto se cubre con
            // TYPE_OUT ya que el engine registra cada out via Play::recordPlay.
            $isRecordedOut = ($t === static::TYPE_OUT)
                || ($t === static::TYPE_PITCH && in_array($st, ['looking', 'swinging', 'foul_tip'], true) && ((int) $p->balls === 3 || (int) $p->strikes === 2));
            // Engine crea jugada TYPE_OUT (SUBTYPE_OUT_STRIKEOUT) por separado
            // cuando se llega a 3 strikes, asi que type=out cubre los ponches
            // de forma fiable. Ademas anade el at_bat_start con type=pitch.
            $delta = (int) $p->outs_after - (int) $p->outs_before;
            if ($delta > 0 && $t === static::TYPE_OUT) {
                $outsByPitcher[$pid] = ($outsByPitcher[$pid] ?? 0) + $delta;
            }
        }
        $pitchingList = [];
        foreach ($pitchingById as $pid => $row) {
            $outs = $outsByPitcher[$pid] ?? 0;
            $ip = number_format(floor($outs / 3), 0) . '.' . ($outs % 3);
            $row['outs'] = $outs;
            $row['ip'] = $ip;
            // Aproximacion: ER = R para esta primera version (sin plays de
            // errores que atribuyan unearned runs en este momento).
            $row['earned_runs'] = $row['runs_allowed'];
            $pitchingList[] = $row;
        }
        // Ordenar por aparicion (primer inning en que lanzo)
        usort($pitchingList, function ($a, $b) use ($plays) {
            $firstA = PHP_INT_MAX; $firstB = PHP_INT_MAX;
            foreach ($plays as $p) {
                if ($p->pitcher_id === $a['pitcher_id']) { $firstA = min($firstA, (int) $p->inning * 2 + ($p->half === 'bottom' ? 1 : 0)); }
                if ($p->pitcher_id === $b['pitcher_id']) { $firstB = min($firstB, (int) $p->inning * 2 + ($p->half === 'bottom' ? 1 : 0)); }
            }
            return $firstA <=> $firstB;
        });

        // ============ BATTING ============
        $battingById = [];
        foreach ($plays as $p) {
            $bid = $p->batter_id;
            if (! $bid) continue;
            if (! isset($battingById[$bid])) {
                $battingById[$bid] = [
                    'batter_id' => $bid,
                    'at_bats' => 0,
                    'hits' => 0,
                    'singles' => 0,
                    'doubles' => 0,
                    'triples' => 0,
                    'hr' => 0,
                    'rbi' => 0,
                    'runs' => 0,
                    'walks' => 0,
                    'strikeouts' => 0,
                    'hbp' => 0,
                ];
            }
            $t = $p->type;
            $st = $p->subtype;
            if ($t === static::TYPE_HIT) {
                $battingById[$bid]['at_bats']++;
                $battingById[$bid]['hits']++;
                $battingById[$bid]['rbi'] += (int) $p->rbi;
                // El bateador anota si es HR o HR de pierna.
                if (in_array($st, [static::SUBTYPE_HIT_HR, static::SUBTYPE_HIT_INSIDE_PARK], true)) {
                    $battingById[$bid]['runs']++;
                }
                if ($st === static::SUBTYPE_HIT_SINGLE) $battingById[$bid]['singles']++;
                if ($st === static::SUBTYPE_HIT_DOUBLE) $battingById[$bid]['doubles']++;
                if ($st === static::SUBTYPE_HIT_TRIPLE) $battingById[$bid]['triples']++;
                if ($st === static::SUBTYPE_HIT_HR) $battingById[$bid]['hr']++;
            } elseif ($t === static::TYPE_OUT) {
                $battingById[$bid]['at_bats']++;
                if ($st === static::SUBTYPE_OUT_STRIKEOUT) {
                    $battingById[$bid]['strikeouts']++;
                }
                $battingById[$bid]['rbi'] += (int) $p->rbi;
            } elseif ($t === static::TYPE_WALK) {
                $battingById[$bid]['walks']++;
                $battingById[$bid]['rbi'] += (int) $p->rbi;
            } elseif ($t === static::TYPE_HBP) {
                $battingById[$bid]['hbp']++;
            } elseif ($t === static::TYPE_BUNT) {
                $battingById[$bid]['at_bats']++;
                $battingById[$bid]['rbi'] += (int) $p->rbi;
            }
        }
        $battingList = [];
        foreach ($battingById as $row) {
            $row['avg'] = $row['at_bats'] > 0 ? round($row['hits'] / $row['at_bats'], 3) : 0.0;
            $battingList[] = $row;
        }
        // Ordenar por orden de bateo (lineup_order) cuando sea posible
        usort($battingList, function ($a, $b) use ($plays) {
            $firstA = PHP_INT_MAX; $firstB = PHP_INT_MAX;
            foreach ($plays as $p) {
                if ($p->batter_id === $a['batter_id']) { $firstA = min($firstA, (int) $p->inning * 2 + ($p->half === 'bottom' ? 1 : 0)); }
                if ($p->batter_id === $b['batter_id']) { $firstB = min($firstB, (int) $p->inning * 2 + ($p->half === 'bottom' ? 1 : 0)); }
            }
            return $firstA <=> $firstB;
        });

        // ============ LINE SCORE (carreras por inning) ============
        $lineScore = ['home' => [], 'away' => []];
        for ($i = 1; $i <= $totalInnings; $i++) {
            $lineScore['home'][$i] = 0;
            $lineScore['away'][$i] = 0;
        }
        foreach ($plays as $p) {
            if ((int) $p->runs_scored > 0 && $p->inning >= 1 && $p->inning <= $totalInnings) {
                if ($p->half === 'top') {
                    $lineScore['away'][$p->inning] += (int) $p->runs_scored;
                } else {
                    $lineScore['home'][$p->inning] += (int) $p->runs_scored;
                }
            }
        }

        return [
            'pitching' => $pitchingList,
            'batting' => $battingList,
            'line_score' => $lineScore,
        ];
    }
}

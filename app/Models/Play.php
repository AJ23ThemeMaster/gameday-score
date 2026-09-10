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

        return [
            'inning' => $last->inning,
            'half' => $last->half,
            'outs' => $last->outs_after,
            'balls' => $last->balls,
            'strikes' => $last->strikes,
            'bases' => $bases,
            'current_batter_id' => $last->batter_id,
            'current_pitcher_id' => $last->pitcher_id,
            'last_play_id' => $last->id,
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

        return [
            'inning' => 1,
            'half' => 'top', // visitante batea primero
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
     * Calcula el score del juego contando carreras por inning.
     */
    public static function scoreboard(int $gameId): array
    {
        $game = Game::find($gameId);

        // Carreras por inning y equipo
        $plays = static::where('game_id', $gameId)
            ->where('runs_scored', '>', 0)
            ->orderBy('inning')
            ->orderBy('half')
            ->orderBy('sequence')
            ->get();

        $byInning = []; // [inning => [top => N, bottom => N]]
        $totals = ['home' => 0, 'away' => 0];

        foreach ($plays as $p) {
            $byInning[$p->inning][$p->half] = ($byInning[$p->inning][$p->half] ?? 0) + $p->runs_scored;
            // top = visitante batea, bottom = local batea
            if ($p->half === 'top') {
                $totals['away'] += $p->runs_scored;
            } else {
                $totals['home'] += $p->runs_scored;
            }
        }

        return [
            'home' => $totals['home'],
            'away' => $totals['away'],
            'by_inning' => $byInning,
        ];
    }
}

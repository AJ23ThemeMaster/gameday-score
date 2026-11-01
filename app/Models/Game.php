<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'tournament_id',
        'stadium_id',
        'home_team_id',
        'away_team_id',
        'scheduled_at',
        'status',
        'is_public',
        'public_token',
        'home_score',
        'away_score',
        'current_inning',
        'inning_half',
        'balls',
        'strikes',
        'outs',
        'bases',
        'innings_count',
        'mercy_rule_difference',
        'mercy_rule_inning',
        'pitch_limit',
        'mercy_rule_applied',
        'scoreboard',
        'roster',
        'started_at',
        'ended_at',
        'winning_pitcher_id',
        'losing_pitcher_id',
        'save_pitcher_id',
        'mvp_athlete_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'is_public' => 'boolean',
            'mercy_rule_applied' => 'boolean',
            'home_score' => 'integer',
            'away_score' => 'integer',
            'current_inning' => 'integer',
            'balls' => 'integer',
            'strikes' => 'integer',
            'outs' => 'integer',
            'innings_count' => 'integer',
            'mercy_rule_difference' => 'integer',
            'mercy_rule_inning' => 'integer',
            'pitch_limit' => 'integer',
            'bases' => 'array',
            'scoreboard' => 'array',
            'roster' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Game $game) {
            if ($game->is_public && empty($game->public_token)) {
                $game->public_token = Str::random(48);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function stadium(): BelongsTo
    {
        return $this->belongsTo(Stadium::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    // DISI-17: atribuciones de jugadores al final del juego
    public function winningPitcher(): BelongsTo
    {
        return $this->belongsTo(Athlete::class, 'winning_pitcher_id');
    }

    public function losingPitcher(): BelongsTo
    {
        return $this->belongsTo(Athlete::class, 'losing_pitcher_id');
    }

    public function savePitcher(): BelongsTo
    {
        return $this->belongsTo(Athlete::class, 'save_pitcher_id');
    }

    public function mvp(): BelongsTo
    {
        return $this->belongsTo(Athlete::class, 'mvp_athlete_id');
    }

    public function scorekeepers(): BelongsToMany
    {
        return $this->belongsToMany(Scorekeeper::class, 'game_scorekeeper')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function referees(): BelongsToMany
    {
        return $this->belongsToMany(Referee::class, 'game_referee')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function athletes(): BelongsToMany
    {
        return $this->belongsToMany(Athlete::class, 'game_athlete')
            ->withPivot([
                'team_id', 'lineup_order', 'position',
                'is_starter', 'is_pitcher', 'pitches_thrown',
                'at_bats', 'hits', 'runs', 'rbi',
            ])
            ->withTimestamps();
    }

    /**
     * Tamano del lineup de bateo de un equipo en este juego.
     *
     * 9 bateadores de posicion (minimo) o 10 cuando hay bateador designado
     * (DH) en el puesto 10 del orden de bateo.
     *
     * La fuente de verdad es `game_athlete.lineup_order` (no `is_starter`):
     * el DH se sembró historicamente con `is_starter = 0` aunque SI ocupa un
     * puesto en el orden de bateo, asi que filtrar por `is_starter` excluia al
     * DH y devolvia 9 en vez de 10.
     */
    public function lineupSizeFor(int $teamId): int
    {
        $maxOrder = (int) $this->athletes()
            ->wherePivot('team_id', $teamId)
            ->whereNotNull('game_athlete.lineup_order')
            ->max('game_athlete.lineup_order');

        return $maxOrder >= 10 ? 10 : 9;
    }

    public function getPublicUrlAttribute(): ?string
    {
        // DISI-44: solo necesitamos public_token para armar la URL publica;
        // is_public ya no es requisito aqui (la politica de visibilidad se
        // aplica por separado en live.blade.php con `@if ($game->is_public &&
        // $game->public_url)`). Asi un juego con token generado pero con
        // is_public=false sigue teniendo URL accesible, solo que el scoreboard
        // no muestra el boton Live para evitar compartirla.
        if (! $this->public_token) {
            return null;
        }

        return url("/game/live/{$this->public_token}");
    }

    /**
     * Motivo de finalizacion del juego (reglamentario/nocaut/suspension/forfait/otro)
     * + nota opcional. Lo extrae del ultimo Play de tipo game_end (subtype=reason,
     * meta->note). Retorna null si el juego no fue finalizado con motivo explicito
     * (subtype=manual se considera "sin motivo seleccionado" y no se muestra).
     */
    public function endGameReason(): ?array
    {
        if ($this->status !== 'finalized') {
            return null;
        }

        $last = Play::where('game_id', $this->id)
            ->where('type', Play::TYPE_GAME_END)
            ->orderByDesc('sequence')
            ->first();

        if (! $last) {
            return null;
        }

        $reason = $last->subtype ?: 'manual';

        // subtype=manual (legacy o juegos cerrados via DB sin pasar por el modal)
        // no deben mostrar burbuja de motivo: no hay informacion que aportar.
        if ($reason === 'manual') {
            return null;
        }

        $meta = $last->meta ?? [];
        $note = $meta['note'] ?? null;

        $labels = [
            'regulation' => 'Reglamentario',
            'knockout' => 'Nocaut / Mercy rule',
            'suspension' => 'Suspendido',
            'forfeit' => 'Forfait / W.O.',
            'other' => 'Otro motivo',
        ];

        return [
            'reason' => $reason,
            'label' => $labels[$reason] ?? 'Finalizado',
            'note' => $note,
        ];
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        // Un juego esta 'completado' tanto cuando el anotador lo finaliza
        // manualmente (status='completed' via GameController@endInning al
        // alcanzar innings_count) como cuando el GameplayEngine detecta
        // automaticamente que el juego se acabo (status='finalized' via
        // GameplayEngine cuando se cumple el limite de innings en una
        // jugada). Ambos estados indican "juego terminado, no se pueden
        // registrar mas jugadas".
        return in_array($this->status, ['completed', 'finalized'], true);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}

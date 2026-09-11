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

    public function getPublicUrlAttribute(): ?string
    {
        if (! $this->is_public || ! $this->public_token) {
            return null;
        }

        return url("/juego/publico/{$this->public_token}");
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
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

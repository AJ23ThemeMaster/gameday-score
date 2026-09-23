<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Roster de un equipo por categoria.
 *
 * Agrupa: manager (Coach role=manager), N coaches adicionales,
 * 1 delegado (User role=delegado), y atletas derivados por
 * team_id + category_id (no requiere pivote).
 */
class Roster extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'category_id',
        'name',
        'manager_coach_id',
        'delegate_user_id',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function managerCoach(): BelongsTo
    {
        return $this->belongsTo(Coach::class, 'manager_coach_id');
    }

    public function delegateUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegate_user_id');
    }

    /**
     * Coaches adicionales del roster (N). El manager se lista aparte.
     */
    public function coaches(): BelongsToMany
    {
        return $this->belongsToMany(Coach::class, 'roster_coach')
            ->withTimestamps();
    }

    /**
     * Atletas del roster: cualquier atleta del equipo. La vista filtra
     * adicionalmente por la categoria del roster en el sitio de uso
     * (`->where('category_id', $roster->category_id)`). Esto evita el bug
     * de eager-loading con closure sobre `$this->category_id` (Laravel
     * clona el QueryBuilder en un contexto donde la instancia del modelo
     * padre no esta completamente construida y la condicion queda como
     * `category_id IS NULL`).
     */
    public function athletes(): HasMany
    {
        return $this->hasMany(Athlete::class, 'team_id', 'team_id');
    }

    /**
     * Scope local: atletas del roster que ademas comparten su categoria.
     * Usar desde el controller con `$roster->categoryAthletes()->...`.
     */
    public function categoryAthletes()
    {
        return $this->hasMany(Athlete::class, 'team_id', 'team_id')
            ->where('category_id', $this->category_id);
    }

    public function getFullNameAttribute(): string
    {
        $team = $this->team?->name ?? '?';
        $category = $this->category?->name ?? '?';
        $name = $this->name ? " ({$this->name})" : '';
        return "{$team} - {$category}{$name}";
    }
}

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
     * (`->whereHas('categories', ...)`). Esto evita el bug
     * de eager-loading con closure sobre `$this->category_id` (Laravel
     * clona el QueryBuilder en un contexto donde la instancia del modelo
     * padre no esta completamente construida y la condicion queda como
     * `category_id IS NULL`).
     *
     * DISI-N: las categorias del atleta viven en la pivot athlete_category.
     * `athletes()` aqui sigue devolviendo TODOS los atletas del team (sin
     * filtro de categoria) para que el caller decida.
     */
    public function athletes(): HasMany
    {
        return $this->hasMany(Athlete::class, 'team_id', 'team_id');
    }

    /**
     * Scope local: atletas del roster que ademas pertenecen a la categoria
     * del roster (via pivot athlete_category). Usar desde el controller
     * con `$roster->categoryAthletes()->...`.
     *
     * DISI-N: ANTES filtraba con `where('category_id', $this->category_id)`
     * directo (atletas con categoria unica). AHORA consulta la pivot, por
     * lo que un atleta multi-cat (ej. PEDRITO en PRE-INFANTIL + INFANTIL)
     * aparece en ambos rosters si coinciden team + categoria correspondiente.
     */
    public function categoryAthletes()
    {
        $rosterCategoryId = (int) $this->category_id;

        return $this->hasMany(Athlete::class, 'team_id', 'team_id')
            ->whereHas('categories', function ($q) use ($rosterCategoryId) {
                $q->where('categories.id', $rosterCategoryId);
            });
    }

    public function getFullNameAttribute(): string
    {
        $team = $this->team?->name ?? '?';
        $category = $this->category?->name ?? '?';
        $name = $this->name ? " ({$this->name})" : '';
        return "{$team} - {$category}{$name}";
    }
}

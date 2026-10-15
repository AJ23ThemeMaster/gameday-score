<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class Athlete extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'document_id',
        'document_file_path',
        'birth_date',
        'photo_path',
        'team_id',
        'number',
        'position',
        'bats',
        'throws',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'team_id' => 'integer',
            'number' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * DISI-N: un atleta puede pertenecer a MAXIMO 2 categorias del mismo
     * equipo. La relacion se materializa via la pivot athlete_category.
     * Validaciones:
     *  - max 2 categorias (regla de negocio).
     *  - todas las categorias deben ser del mismo team_id que el atleta
     *    (delegado de PRE-INFANTIL no puede administrar atletas en el
     *    roster de INFANTIL de otro equipo, etc.).
     *
     * El helper syncCategories() se ocupa de esas validaciones; el form
     * request solo valida que sean enteros existentes (max:2) y deja las
     * invariantes de equipo al helper.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'athlete_category')
            ->withTimestamps();
    }

    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'game_athlete')
            ->withPivot(['team_id', 'lineup_order', 'position', 'is_starter', 'is_pitcher', 'pitches_thrown', 'at_bats', 'hits', 'runs', 'rbi'])
            ->withTimestamps();
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->photo_path);
    }

    public function getDocumentUrlAttribute(): ?string
    {
        if (! $this->document_file_path) {
            return null;
        }

        return Storage::disk('public')->url($this->document_file_path);
    }

    public function getDocumentIsImageAttribute(): bool
    {
        if (! $this->document_file_path) {
            return false;
        }

        $ext = strtolower(pathinfo($this->document_file_path, PATHINFO_EXTENSION));

        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true);
    }

    public function getDocumentIsPdfAttribute(): bool
    {
        if (! $this->document_file_path) {
            return false;
        }

        return strtolower(pathinfo($this->document_file_path, PATHINFO_EXTENSION)) === 'pdf';
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopePitchers($query)
    {
        return $query->whereHas('games', function ($q) {
            $q->where('game_athlete.is_pitcher', true);
        });
    }

    /**
     * Helper central de sincronizacion de las categorias del atleta.
     *
     * Reglas (todas se validan antes de sync; si alguna falla, lanza
     * ValidationException con el mensaje user-facing en espanol):
     *  1. Sin duplicados.
     *  2. Maximo 2 categorias.
     *  3. Todas las categorias deben existir.
     *  4. Todas las categorias deben pertenecer al MISMO team_id que el
     *     atleta (regla de "mismo equipo").
     *
     * Caso borde: si $this->team_id es null y el atleta no existe en BDD
     * todavia, se llama DESPUES de $athlete->save() para que team_id este
     * disponible. Si el atleta ya existe pero team_id es null, igual se
     * permite sincronizar (caso "atleta sin equipo"), siempre que las
     * categorias tampoco tengan team_id (categoria global / sin asignar).
     *
     * @param  array<int|string>  $categoryIds
     */
    public function syncCategories(array $categoryIds): void
    {
        // Limpiar + des-duplicar + cast a int.
        $categoryIds = collect($categoryIds)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        // Sin IDs => limpiar pivot y salir.
        if (empty($categoryIds)) {
            $this->categories()->sync([]);

            return;
        }

        // Regla 2: maximo 2 categorias.
        if (count($categoryIds) > 2) {
            throw ValidationException::withMessages([
                'categories' => 'Un atleta puede pertenecer a máximo 2 categorías.',
            ]);
        }

        // Regla 3: todas las categorias deben existir.
        $cats = Category::whereIn('id', $categoryIds)->get();
        if ($cats->count() !== count($categoryIds)) {
            throw ValidationException::withMessages([
                'categories' => 'Una o más categorías seleccionadas no existen.',
            ]);
        }

        // Regla 4: misma team que el atleta. Comparamos contra el team_id
        // actual del atleta; categorias con team_id NULL (categorias
        // globales) se consideran validas para atletas sin equipo.
        if ($this->team_id !== null) {
            $wrongTeam = $cats->first(function (Category $c) {
                return $c->team_id !== null && (int) $c->team_id !== (int) $this->team_id;
            });
            if ($wrongTeam !== null) {
                throw ValidationException::withMessages([
                    'categories' => 'Todas las categorías deben pertenecer al mismo equipo del atleta.',
                ]);
            }
        } else {
            // Atleta sin equipo: ninguna categoria puede tener team_id.
            if ($cats->contains(fn (Category $c) => $c->team_id !== null)) {
                throw ValidationException::withMessages([
                    'categories' => 'No puedes asignar categorías de un equipo a un atleta sin equipo.',
                ]);
            }
        }

        // Sincronizacion atomica: insert nuevas + detach sobrantes.
        $this->categories()->sync($categoryIds);
    }
}

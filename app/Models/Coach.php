<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Coach (entrenador) de un equipo.
 *
 * Puede o no estar vinculado a un User del sistema (FK opcional).
 * El campo role es libre (string) para soportar roles custom:
 * manager, head, bench, pitching, hitting, assistant, etc.
 */
class Coach extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
        'first_name',
        'last_name',
        'document_id',
        'phone',
        'email',
        'role',
        'birth_date',
        'notes',
        'active',
        'photo_path',
        'document_photo_path',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'active' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function rosters(): BelongsToMany
    {
        return $this->belongsToMany(Roster::class, 'roster_coach')
            ->withTimestamps();
    }

    /**
     * Rosters donde este coach es el manager.
     */
    public function managedRosters(): HasMany
    {
        return $this->hasMany(Roster::class, 'manager_coach_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * URL publica de la foto del coach (o null si no tiene).
     * Usa el disco 'public' que se sirve via symlink storage/.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }
        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->photo_path);
    }

    /**
     * URL publica de la foto del documento de identidad (o null).
     */
    public function getDocumentPhotoUrlAttribute(): ?string
    {
        if (! $this->document_photo_path) {
            return null;
        }
        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->document_photo_path);
    }
}

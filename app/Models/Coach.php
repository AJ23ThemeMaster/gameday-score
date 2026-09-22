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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }
}

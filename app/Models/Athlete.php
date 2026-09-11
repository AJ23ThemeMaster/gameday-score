<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Athlete extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'document_id',
        'birth_date',
        'photo_path',
        'team_id',
        'category_id',
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
            'category_id' => 'integer',
            'number' => 'integer',
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
}

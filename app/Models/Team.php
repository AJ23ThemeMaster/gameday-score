<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'city',
        'logo_path',
        'home_color',
        'away_color',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function athletes(): HasMany
    {
        return $this->hasMany(Athlete::class);
    }

    public function homeGames(): HasMany
    {
        return $this->hasMany(Game::class, 'home_team_id');
    }

    public function awayGames(): HasMany
    {
        return $this->hasMany(Game::class, 'away_team_id');
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->logo_path);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}

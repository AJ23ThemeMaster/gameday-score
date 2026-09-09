<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'innings_count',
        'mercy_rule_difference',
        'mercy_rule_inning',
        'pitch_limit',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'innings_count' => 'integer',
            'mercy_rule_difference' => 'integer',
            'mercy_rule_inning' => 'integer',
            'pitch_limit' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}

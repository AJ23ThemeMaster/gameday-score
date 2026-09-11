<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'league_id',
        'name',
        'slug',
        'logo_path',
        'category',
        'season',
        'starts_at',
        'ends_at',
        'description',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'league_id' => 'integer',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Tournament $t) {
            if (empty($t->slug)) {
                $t->slug = static::generateUniqueSlug($t->name, $t->league_id);
            }
        });

        static::updating(function (Tournament $t) {
            if ($t->isDirty('name') && ! $t->isDirty('slug')) {
                $t->slug = static::generateUniqueSlug($t->name, $t->league_id, $t->id);
            }
            if ($t->isDirty('league_id') && ! $t->isDirty('slug')) {
                $t->slug = static::generateUniqueSlug($t->name, $t->league_id, $t->id);
            }
        });
    }

    public static function generateUniqueSlug(string $name, int|string $leagueId, ?int $ignoreId = null): string
    {
        // PHP 8.4 strict types: aceptar string|int y castear defensivo
        // porque la validacion de Laravel a veces pasa el league_id como
        // string cuando viene de un FormRequest.
        $leagueId = (int) $leagueId;
        $ignoreId = $ignoreId !== null ? (int) $ignoreId : null;

        $base = Str::slug($name);
        $slug = $base;
        $i = 1;
        $query = static::query()
            ->where('league_id', $leagueId)
            ->where('slug', $slug);
        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }
        while ($query->exists()) {
            $slug = $base . '-' . (++$i);
            $query = static::query()
                ->where('league_id', $leagueId)
                ->where('slug', $slug);
            if ($ignoreId !== null) {
                $query->where('id', '!=', $ignoreId);
            }
        }

        return $slug;
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        if (Str::startsWith($this->logo_path, ['http://', 'https://'])) {
            return $this->logo_path;
        }

        return Storage::disk('public')->url($this->logo_path);
    }
}

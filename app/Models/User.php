<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Juegos donde este usuario figura como anotador (scorekeeper).
     * Relacion a traves del pivot game_scorekeeper.
     */
    public function gamesAsScorekeeper(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'game_scorekeeper')
            ->withPivot(['assigned_at'])
            ->withTimestamps();
    }

    /**
     * Juegos donde este usuario figura como referee.
     */
    public function gamesAsReferee(): BelongsToMany
    {
        return $this->belongsToMany(Game::class, 'game_referee')
            ->withPivot(['assigned_at'])
            ->withTimestamps();
    }

    /**
     * Verificar si este usuario es administrador.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Verificar si este usuario es anotador.
     */
    public function isAnotador(): bool
    {
        return $this->hasRole('anotador');
    }
}

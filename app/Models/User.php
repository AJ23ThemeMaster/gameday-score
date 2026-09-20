<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_path',
        'team_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            // 2FA: cifrar en reposo con APP_KEY
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted',
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

    /**
     * DISI-81: verificar si este usuario es gestor de su equipo asociado.
     * El gestor solo puede administrar el equipo al que esta asociado y los
     * atletas de ese equipo. Requiere tanto el rol 'gestor' como un team_id
     * no nulo.
     */
    public function isGestor(): bool
    {
        return $this->hasRole('gestor') && $this->team_id !== null;
    }

    /**
     * DISI-81: el gestor actua sobre el modelo dado? Solo si el team_id del
     * modelo coincide con el team_id del usuario. Para el modelo Team el
     * identificador relevante es el propio id (no team_id, que no existe).
     */
    public function isGestorOwning($model): bool
    {
        if (! $this->isGestor()) {
            return false;
        }
        // Team: usar id directamente. Athlete y otros con team_id: usar team_id.
        $modelTeamId = $model instanceof Team ? $model->id : ($model->team_id ?? null);

        return $modelTeamId !== null && (int) $modelTeamId === (int) $this->team_id;
    }

    /**
     * DISI-80: equipo al que esta asociado el usuario (nullable).
     * Un admin del sistema puede o no tener equipo asociado.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    // ---------------------------------------------------------------------
    // DISI-16: perfil + 2FA
    // ---------------------------------------------------------------------

    /**
     * URL publica del avatar (o null si no tiene).
     * Usa storage/app/public en disco 'public'.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if (! $this->avatar_path) {
                    return null;
                }
                if (str_starts_with($this->avatar_path, 'http')) {
                    return $this->avatar_path;
                }

                return Storage::disk('public')->url($this->avatar_path);
            },
        );
    }

    /**
     * Iniciales para el placeholder del avatar.
     */
    protected function avatarInitials(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $name = trim($this->name ?? '');
                if ($name === '') {
                    return '?';
                }
                $parts = preg_split('/\s+/', $name) ?: [];
                $first = mb_substr($parts[0] ?? '', 0, 1);
                $second = mb_substr($parts[1] ?? '', 0, 1);

                return mb_strtoupper($first.($second !== '' ? $second : ''));
            },
        );
    }

    /**
     * 2FA ya activado y confirmado por el usuario.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null
            && ! empty($this->two_factor_secret);
    }

    /**
     * Generar un nuevo secreto TOTP (no persistido).
     * Se persiste solo cuando el usuario lo confirma.
     */
    public function generateTwoFactorSecret(): string
    {
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        $this->two_factor_secret = $secret;
        $this->save();

        return $secret;
    }

    /**
     * Confirmar y activar el 2FA una vez verificado el código por el usuario.
     * Genera codigos de recuperacion y los persiste.
     *
     * @return array<int, string> codigos de recuperacion generados
     */
    public function enableTwoFactor(): array
    {
        if (empty($this->two_factor_secret)) {
            $this->generateTwoFactorSecret();
        }

        $recoveryCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $recoveryCodes[] = strtoupper(bin2hex(random_bytes(5)));
        }

        $this->two_factor_recovery_codes = json_encode($recoveryCodes);
        $this->two_factor_confirmed_at = now();
        $this->save();

        return $recoveryCodes;
    }

    /**
     * Desactivar 2FA y limpiar secreto/codigos.
     */
    public function disableTwoFactor(): void
    {
        $this->two_factor_secret = null;
        $this->two_factor_recovery_codes = null;
        $this->two_factor_confirmed_at = null;
        $this->save();
    }

    /**
     * Verificar un codigo TOTP contra el secreto del usuario.
     */
    public function verifyTwoFactorCode(string $code): bool
    {
        if (empty($this->two_factor_secret)) {
            return false;
        }

        $google2fa = new Google2FA();

        return $google2fa->verifyKey($this->two_factor_secret, $code);
    }

    /**
     * Decodificar los codigos de recuperacion persistidos.
     *
     * @return array<int, string>
     */
    public function twoFactorRecoveryCodes(): array
    {
        if (empty($this->two_factor_recovery_codes)) {
            return [];
        }

        $decoded = json_decode((string) $this->two_factor_recovery_codes, true);

        return is_array($decoded) ? $decoded : [];
    }
}

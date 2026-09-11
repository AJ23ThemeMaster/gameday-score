<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DISI-16: agrega campos de perfil y 2FA a la tabla users.
 *  - avatar_path: foto de perfil subida por el usuario
 *  - two_factor_secret: secreto TOTP cifrado (Google Authenticator)
 *  - two_factor_recovery_codes: códigos de recuperación cifrados
 *  - two_factor_confirmed_at: timestamp de activación del 2FA
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('avatar_path')->nullable()->after('password');
            $table->text('two_factor_secret')->nullable()->after('avatar_path');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'avatar_path',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
            ]);
        });
    }
};

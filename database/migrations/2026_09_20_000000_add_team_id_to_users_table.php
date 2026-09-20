<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // DISI-80: asociar usuario a un equipo (opcional). La asociacion es
        // 1 a 1 simple (no M:N): un usuario pertenece como maximo a un equipo.
        // Nullable porque no todos los usuarios necesitan asociacion (admins
        // siguen manejando todo el sistema).
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->after('avatar_path')
                ->constrained('teams')->nullOnDelete();
            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
        });
    }
};
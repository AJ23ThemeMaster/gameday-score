<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // DISI-14b: 1 equipo pertenece a 1 LIGA (no torneo). El torneo queda
        // reservado para juegos/programacion, no para equipo. La categoria
        // y la relacion equipo->categoria (1 equipo -> N categorias) se
        // mantienen del DISI-14.
        Schema::table('teams', function (Blueprint $table) {
            // Eliminar FK + columna tournament_id
            $table->dropConstrainedForeignId('tournament_id');
        });
        Schema::table('teams', function (Blueprint $table) {
            // Agregar league_id como nueva FK
            $table->foreignId('league_id')->nullable()->after('id')
                ->constrained('leagues')->nullOnDelete();
            $table->index('league_id');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('league_id');
        });
        Schema::table('teams', function (Blueprint $table) {
            $table->foreignId('tournament_id')->nullable()->after('id')
                ->constrained('tournaments')->nullOnDelete();
            $table->index('tournament_id');
        });
    }
};

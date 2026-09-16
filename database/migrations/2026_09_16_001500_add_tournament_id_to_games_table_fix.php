<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // DISI-68: la migracion 2026_09_10_200002_add_tournament_id_to_games_table
        // aparece como aplicada en la tabla `migrations` pero la columna NO
        // existe en `games` (estado inconsistente del DB local). Esta migracion
        // agrega la columna con guard para que sea idempotente: si ya existe
        // (caso produccion donde se aplico bien), no hace nada.
        if (! Schema::hasColumn('games', 'tournament_id')) {
            Schema::table('games', function (Blueprint $table) {
                $table->foreignId('tournament_id')->nullable()->after('category_id')
                    ->constrained('tournaments')->nullOnDelete();
                $table->index('tournament_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('games', 'tournament_id')) {
            Schema::table('games', function (Blueprint $table) {
                $table->dropConstrainedForeignId('tournament_id');
            });
        }
    }
};
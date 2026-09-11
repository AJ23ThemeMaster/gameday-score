<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DISI-17: agrega atribuciones de jugadores al final del juego:
 *  - winning_pitcher_id: pitcher ganador
 *  - losing_pitcher_id:  pitcher perdedor
 *  - save_pitcher_id:    pitcher con juego salvado (opcional)
 *  - mvp_athlete_id:     jugador mas valioso del juego
 *
 * Las FK son nullables porque el juego puede no estar finalizado o no tener
 * pitchers asignados al roster todavia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table): void {
            $table->foreignId('winning_pitcher_id')
                ->nullable()
                ->after('ended_at')
                ->constrained('athletes')
                ->nullOnDelete();
            $table->foreignId('losing_pitcher_id')
                ->nullable()
                ->after('winning_pitcher_id')
                ->constrained('athletes')
                ->nullOnDelete();
            $table->foreignId('save_pitcher_id')
                ->nullable()
                ->after('losing_pitcher_id')
                ->constrained('athletes')
                ->nullOnDelete();
            $table->foreignId('mvp_athlete_id')
                ->nullable()
                ->after('save_pitcher_id')
                ->constrained('athletes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('winning_pitcher_id');
            $table->dropConstrainedForeignId('losing_pitcher_id');
            $table->dropConstrainedForeignId('save_pitcher_id');
            $table->dropConstrainedForeignId('mvp_athlete_id');
        });
    }
};

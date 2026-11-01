<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Alinea `game_athlete.is_starter` con `game_athlete.lineup_order`.
 *
 * CONTEXTO:
 * El bateador designado (DH) ocupa el puesto 10 del orden de bateo, pero se
 * sembro historicamente con `is_starter = 0` (quien lo marco como titular fue
 * solo el que ocupa una posicion defensiva). Como el backend usaba
 * `is_starter = 1` para calcular el tamano del lineup, el DH quedaba FUERA del
 * conteo: un lineup de 10 se reportaba como 9 y el modal "Gestion de Lineup"
 * bloqueaba con "Faltan -1 titulares" / "Sobran 1 titulares".
 *
 * La fuente de verdad del lineup es `lineup_order` (ver Game::lineupSizeFor()
 * y ScoreboardController::reorderLineup(), que ya sincronizan is_starter).
 * Esta migracion solo alinea el dato ya guardado en los rows existentes.
 *
 * Es IDEMPOTENTE: solo toca filas que tienen lineup_order > 0 y is_starter = 0.
 */
return new class extends Migration
{
    public function up(): void
    {
        $affected = DB::table('game_athlete')
            ->whereNotNull('lineup_order')
            ->where('lineup_order', '>', 0)
            ->where('is_starter', false)
            ->update(['is_starter' => true, 'updated_at' => now()]);

        if ($affected > 0) {
            Log::info("game_athlete: se alinearon {$affected} filas con lineup_order pero is_starter=0 (bateadores designados).");
        } else {
            Log::info('game_athlete: no habia filas con lineup_order e is_starter=0; nada que alinear.');
        }
    }

    public function down(): void
    {
        // Irreversible a proposito: no se puede saber cuales filas tenian
        // is_starter=0 por decision del admin (DH) y cuales por desalineacion.
    }
};

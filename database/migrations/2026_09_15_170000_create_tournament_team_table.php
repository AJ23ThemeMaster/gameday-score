<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DISI-57: pivot M:N entre tournaments y teams.
     *
     * Justificacion del diseno:
     *  - Antes: Tournament solo tenia Games (no tenia relacion directa con Teams).
     *    Los teams pertenecian a la league (DISI-14b), no al tournament.
     *  - Ahora: un Tournament puede incluir N teams (los que participan en el)
     *    y un Team puede estar en N tournaments de su league.
     *  - Regla de Frank: un equipo pertenece a 1 sola league. Esto se enforce
     *    desde el form de Team (league_id) y desde este pivot (solo se pueden
     *    asociar teams del mismo league que el tournament).
     *  - FK con onDelete cascade: si se borra un tournament o un team, la fila
     *    pivot desaparece automaticamente (no quedan asociaciones colgadas).
     *  - UNIQUE (tournament_id, team_id) para evitar duplicados.
     */
    public function up(): void
    {
        Schema::create('tournament_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->timestamps();

            // Un equipo no puede estar dos veces en el mismo torneo.
            $table->unique(['tournament_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_team');
    }
};

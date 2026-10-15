<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DISI-N: un atleta puede pertenecer a MAXIMO 2 categorias del mismo equipo.
 * Antes: la categoria estaba en athletes.category_id (FK single).
 * Ahora: pivot athlete_category con UNIQUE(athlete_id, category_id).
 *
 * Restricciones de integridad:
 *  - UNIQUE(athlete_id, category_id) -> no duplicados de la misma categoria.
 *  - cascadeOnDelete en ambos FKs -> borrar atleta o categoria limpia la pivot.
 *  - Validacion de "todas las categorias del mismo team_id" se hace a nivel
 *    aplicacion (Athlete::syncCategories) porque MySQL no soporta CHECK
 *    sobre otras tablas (categories.team_id vs athlete_category).
 *
 * Migracion de datos: el backfill + drop de athletes.category_id va en la
 * siguiente migracion (2026_10_14_120100_...) para que sea atomica.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('athlete_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('athlete_id')
                ->constrained('athletes')
                ->cascadeOnDelete();
            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnDelete();
            $table->timestamps();

            // No se puede asignar la misma categoria 2 veces al mismo atleta.
            $table->unique(['athlete_id', 'category_id'], 'athlete_category_unique');
            // FKs ya indexan automaticamente en MySQL, pero agregamos
            // indices explicitos para busquedas en la otra direccion
            // (ej. "atletas de esta categoria").
            $table->index('category_id', 'athlete_category_category_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('athlete_category');
    }
};

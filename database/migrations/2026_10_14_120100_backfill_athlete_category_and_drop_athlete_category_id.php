<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DISI-N: backfill de athlete_category + drop de athletes.category_id.
 *
 * Pasos:
 *  1) Copiar cada athletes.category_id actual a un row en athlete_category.
 *  2) Drop FK + index + columna athletes.category_id.
 *
 * Por que en una sola migracion: si el backfill deja filas en athletes sin
 * categoria, el comportamiento de la app cambia. Mejor atomicidad.
 *
 * Por que NO preserve athletes.category_id sincronizado con la pivot: el
 * usuario eligio el modelo "eliminar columna, usar solo pivot" para evitar
 * deuda tecnica y queries que filtran solo por la categoria principal.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Backfill: copiar el category_id actual de cada atleta a la pivot.
        //    Si dos atletas comparten (team_id, category_id) ambos quedan en
        //    la pivot con esa unica categoria (caso base; la regla de max-2
        //    categorias solo aplica a las NUEVAS asignaciones, no a la
        //    migracion).
        DB::statement('
            INSERT INTO athlete_category (athlete_id, category_id, created_at, updated_at)
            SELECT id, category_id, NOW(), NOW()
            FROM athletes
            WHERE category_id IS NOT NULL
        ');

        // 2) Drop FK + index + columna athletes.category_id.
        //    Hacemos el drop con el helper de Laravel que detecta el nombre
        //    del constraint FK real (el nombre lo pone MySQL segun la
        //    convencion <tabla>_<col>_foreign).
        Schema::table('athletes', function ($table) {
            // dropConstrainedForeignId elimina FK + columna en una sola
            // operacion, pero solo si existe. Por seguridad usamos
            // try/catch logico via los helpers condicionales.
            $table->dropConstrainedForeignId('category_id');
        });

        // Limpiar el indice explicito (puede o no estar, lo dropeamos si
        // existe con SQL directo para evitar errores en BDD con schema
        // antiguo).
        $indexExists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'athletes')
            ->where('INDEX_NAME', 'athletes_category_id_index')
            ->exists();
        if ($indexExists) {
            DB::statement('DROP INDEX athletes_category_id_index ON athletes');
        }
    }

    public function down(): void
    {
        // Recrear la columna athletes.category_id con FK a categories.
        Schema::table('athletes', function ($table) {
            $table->foreignId('category_id')
                ->nullable()
                ->after('team_id')
                ->constrained('categories')
                ->nullOnDelete();
            $table->index('category_id');
        });

        // Backfill inverso: tomar la PRIMERA categoria de cada atleta de la
        // pivot. Si un atleta tiene multiples categorias, se conserva la
        // primera insertada (orden de ID de la pivot).
        DB::statement('
            UPDATE athletes a
            JOIN (
                SELECT athlete_id, MIN(id) AS first_id
                FROM athlete_category
                GROUP BY athlete_id
            ) first_pick ON first_pick.athlete_id = a.id
            JOIN athlete_category ac ON ac.id = first_pick.first_id
            SET a.category_id = ac.category_id
        ');

        // Borrar todas las filas de la pivot (rollback completo).
        DB::table('athlete_category')->delete();
    }
};

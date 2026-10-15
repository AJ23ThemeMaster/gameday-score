<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * DISI-N: migracion de defensa / auditoria para asegurar que el backfill de
 * las categorias en la pivot athlete_category se realizo correctamente
 * durante el despliegue. Es IDEMPOTENTE: puede correr varias veces sin
 * duplicar datos.
 *
 * Caso de uso:
 *  - En esta BDD el backfill se hizo en 2026_10_14_120100 y quedo 100%
 *    cubierto (84 atletas, 84 entradas en pivot).
 *  - En BDDs donde atletas.category_id era NULL (legacy) o donde la
 *    migracion previa fallo a medias, este script verifica y rellena lo
 *    que falte SIN pisar lo que ya esta.
 *
 * Decisiones del usuario (oct 2026):
 *  - Los atletas con category_id NULL en la legacy BDD quedan
 *    INTENCIONALMENTE sin categoria en la pivot. NO se les asigna una
 *    categoria por defecto automaticamente.
 *  - Solo se reporta en LOG (info + warning) cuantos atletas quedaron
 *    sin categoria, para que el admin/gestor los asigne manualmente via
 *    el multi-select de la UI.
 *
 * Comportamiento:
 *  1. Detecta atletas que tienen un athletes.category_id legacy que
 *     todavia NO esta en la pivot -> los copia (idempotente via NOT EXISTS).
 *  2. Detecta atletas que NO tienen ninguna entrada en la pivot ->
 *     los reporta como "atletas sin categoria" en el log sin tocarlos.
 *  3. NO falla la migracion si encuentra atletas NULL (no rompe
 *     despliegues del cliente si la data legacy tiene nulls).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Solo aplica si la columna legacy AUN existe (es decir, si la
        // migracion de drop no se ha corrido todavia en esta BDD).
        $legacyColExists = Schema::hasColumn('athletes', 'category_id');

        if ($legacyColExists) {
            // Rellenar la pivot con cualquier fila legacy que falte.
            // NOT EXISTS hace la migracion idempotente: si ya esta en la
            // pivot, no la duplica.
            DB::statement('
                INSERT INTO athlete_category (athlete_id, category_id, created_at, updated_at)
                SELECT a.id, a.category_id, NOW(), NOW()
                FROM athletes a
                WHERE a.category_id IS NOT NULL
                  AND NOT EXISTS (
                      SELECT 1 FROM athlete_category ac
                      WHERE ac.athlete_id = a.id AND ac.category_id = a.category_id
                  )
            ');

            Log::info('athlete_category: backfill idempotente ejecutado por ensure_athlete_category_backfill.');
        } else {
            Log::info('athlete_category: columna legacy athletes.category_id ya no existe; backfill legacy no aplica.');
        }

        // Auditar cuantos atletas quedaron sin categoria en la pivot.
        // Esto sirve para alertar al admin del cliente de que debe
        // asignar categorias manualmente via UI.
        $athletesTotal = DB::table('athletes')->count();
        $athletesWithCategory = DB::table('athlete_category')
            ->distinct()
            ->count('athlete_id');
        $athletesWithoutCategory = $athletesTotal - $athletesWithCategory;

        if ($athletesWithoutCategory > 0) {
            Log::warning("athlete_category: hay {$athletesWithoutCategory} atletas sin categoria en la pivot. Asignar manualmente via UI multi-select (maximo 2 categorias del mismo equipo).", [
                'athletes_total' => $athletesTotal,
                'with_category' => $athletesWithCategory,
                'without_category' => $athletesWithoutCategory,
            ]);

            // Listar los IDs para que el admin los identifique rapido.
            $missingIds = DB::table('athletes')
                ->select('id')
                ->whereNotIn('id', function ($q) {
                    $q->select('athlete_id')->from('athlete_category');
                })
                ->limit(50)
                ->pluck('id')
                ->all();
            Log::warning('athlete_category: primeros IDs sin categoria: ' . implode(', ', $missingIds));
        } else {
            Log::info("athlete_category: cobertura OK, {$athletesWithCategory}/{$athletesTotal} atletas tienen al menos 1 categoria.");
        }
    }

    public function down(): void
    {
        // No hay accion reversible: este es un script de auditoria.
        // Revertir la migracion previa de backfill+drop requeriria
        // correr el down() de 2026_10_14_120100, que es independiente.
    }
};

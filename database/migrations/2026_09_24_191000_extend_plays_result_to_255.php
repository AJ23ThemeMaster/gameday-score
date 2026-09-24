<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extiende la columna `result` de la tabla `plays` de varchar(60) a
     * varchar(255). El string(60) original era suficiente para descripciones
     * cortas tipo "6-3 groundout" o "single to LF", pero las acciones de
     * corredor (passed_ball, wild_pitch, OBS, pickoff, out_at_advance)
     * producen mensajes mas largos con el nombre completo del atleta:
     *
     *   "Sebastián A Mudarra García avanza por Passed Ball a Home (+1 carrera)"
     *
     * Eso son ~60 chars con caracteres multibyte (acentos), lo que excede el
     * limite y produce SQLSTATE 22001 (1406) al insertar.
     *
     * 255 es seguro para utf8mb4 (hasta 1020 bytes), cubre cualquier
     * descripcion razonable de jugada sin tocar la columna meta (que ya
     * es json y soporta datos estructurados mas largos).
     */
    public function up(): void
    {
        Schema::table('plays', function (Blueprint $table) {
            $table->string('result', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('plays', function (Blueprint $table) {
            // Revertir al limite original truncando valores que no quepan.
            DB::statement("UPDATE plays SET result = LEFT(result, 60) WHERE LENGTH(result) > 60");
            $table->string('result', 60)->nullable()->change();
        });
    }
};
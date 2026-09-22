<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DISI-delegado: anade category_id al usuario para soportar el rol
 * "delegado" (gestor de UN equipo + UNA categoria especifica).
 * Nullable: solo los delegados la necesitan; admins, anotadores y
 * gestores sin categoria asignada quedan con NULL.
 *
 * Sin FK onDelete cascade: si la categoria se elimina, el delegado
 * queda con category_id apuntando a NULL. Es preferible a perder
 * trazabilidad de que existio. El admin deberia reasignar antes
 * de borrar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->after('team_id');

            // Indice para que el scope de atletas por (team_id, category_id)
            // en el dashboard del delegado no haga full scan.
            $table->index(['team_id', 'category_id']);

            $table->foreign('category_id')
                ->references('id')->on('categories')
                ->onUpdate('cascade')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropIndex(['team_id', 'category_id']);
            $table->dropColumn('category_id');
        });
    }
};

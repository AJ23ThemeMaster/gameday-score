<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entrenadores (coaches) por equipo.
 *
 * Cada equipo tiene uno o varios entrenadores (manager, bench coach,
 * pitching coach, hitting coach, etc.). Esta tabla guarda la relacion
 * equipo <-> coach. El coach puede estar opcionalmente vinculado a
 * un User del sistema (FK nullable a users).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('coaches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('document_id', 50)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('role', 80)->nullable()->comment('manager / head / bench / pitching / hitting / assistant / etc.');
            $table->date('birth_date')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            // Documento unico por equipo (no global: cada equipo puede
            // tener un coach con la misma cedula que en otro equipo).
            $table->unique(['team_id', 'document_id'], 'coaches_team_doc_unique');

            // Indice por team para listar rapido.
            $table->index(['team_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coaches');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Roster de un equipo por categoria.
 *
 * Cada (team, category) tiene un roster donde:
 *  - manager_coach_id: 1 coach con role='manager' (FK a coaches, nullable)
 *  - delegate_user_id: 1 user con rol 'delegado' (FK a users, nullable)
 *  - coaches[]: N coaches adicionales (tabla pivote roster_coach)
 *  - athletes[]: atletas del team con esa categoria (derivado de
 *    athletes.team_id + athletes.category_id; no necesita pivote).
 *
 * El nombre es opcional para distinguir temporadas (ej. "Temporada 2026").
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('rosters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('name')->nullable()->comment('Temporada o alias (opcional)');
            // Manager: coach con role=manager del equipo.
            $table->foreignId('manager_coach_id')
                  ->nullable()
                  ->constrained('coaches')
                  ->nullOnDelete();
            // Delegado: user con rol delegado asignado a este roster.
            $table->foreignId('delegate_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();

            // Un roster por (team, category, name). Si name es null,
            // un team+category solo puede tener 1 roster sin nombre.
            $table->unique(['team_id', 'category_id', 'name'], 'rosters_team_cat_name_unique');
            $table->index(['team_id', 'category_id', 'active']);
        });

        // Tabla pivote para los coaches adicionales (N) del roster.
        Schema::create('roster_coach', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roster_id')->constrained('rosters')->cascadeOnDelete();
            $table->foreignId('coach_id')->constrained('coaches')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['roster_id', 'coach_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roster_coach');
        Schema::dropIfExists('rosters');
    }
};

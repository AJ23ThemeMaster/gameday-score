<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('stadium_id')->nullable()->constrained('stadiums')->nullOnDelete();
            $table->foreignId('home_team_id')->constrained('teams')->restrictOnDelete();
            $table->foreignId('away_team_id')->constrained('teams')->restrictOnDelete();

            $table->dateTime('scheduled_at')->index();
            $table->string('status', 20)->default('scheduled')->index()
                ->comment('scheduled|in_progress|paused|completed|suspended|cancelled');

            $table->boolean('is_public')->default(false)->index();
            $table->string('public_token', 64)->nullable()->unique();

            // Estado del scoreboard
            $table->unsignedTinyInteger('home_score')->default(0);
            $table->unsignedTinyInteger('away_score')->default(0);
            $table->unsignedTinyInteger('current_inning')->default(1);
            $table->enum('inning_half', ['top', 'bottom'])->default('top');
            $table->unsignedTinyInteger('balls')->default(0);
            $table->unsignedTinyInteger('strikes')->default(0);
            $table->unsignedTinyInteger('outs')->default(0);
            $table->json('bases')->nullable()->comment('Estado de corredores en bases');

            // Reglas del juego (snapshot desde category al momento de crearlo)
            $table->unsignedTinyInteger('innings_count')->default(7);
            $table->unsignedTinyInteger('mercy_rule_difference')->default(10);
            $table->unsignedTinyInteger('mercy_rule_inning')->default(5);
            $table->unsignedSmallInteger('pitch_limit')->nullable();
            $table->boolean('mercy_rule_applied')->default(false);

            // Datos completos del scoreboard (inning-by-inning runs/hits/errors)
            $table->json('scoreboard')->nullable();

            // Roster (alineaciones, sustituciones, lanzadores) - estado vivo
            $table->json('roster')->nullable();

            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indices compuestos para queries frecuentes
            $table->index(['user_id', 'status']);
            $table->index(['is_public', 'status', 'scheduled_at']);
            $table->index(['home_team_id', 'scheduled_at']);
            $table->index(['away_team_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};

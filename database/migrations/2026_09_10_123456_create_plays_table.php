<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `plays`: registro cronologico de TODAS las acciones de un juego
 * (pitches, jugadas de bateo, outs, hits, sustituciones, balks, etc.).
 *
 * El campo `meta` (JSON) guarda datos especificos de cada tipo:
 *  - pitch:    { balls: 0, strikes: 1, type: 'swinging'|'looking'|'foul_tip'|'ball'|'foul' }
 *  - hit:      { kind: 'single'|'double'|'triple'|'hr'|'inside_park', rbi: 1 }
 *  - out:      { kind: 'fly'|'line'|'ground'|'strikeout'|'force'|'tag', fielders: [6,4] }
 *  - substitution: { out_athlete_id, in_athlete_id, position, lineup_order }
 *  - inning_end / game_end: { reason, runs_home, runs_away }
 *  - bunt:     { kind: 'sacrifice'|'bunt_single'|'bunt_out' }
 *  - balk:     { advances: [{athlete_id, from, to}] }
 *  - runner_movement: { athlete_id, from, to, reason }
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->unsignedTinyInteger('inning');
            $table->enum('half', ['top', 'bottom'])->default('top');
            $table->unsignedInteger('sequence'); // 1, 2, 3... en orden cronologico del juego

            // Clasificacion
            $table->enum('type', ['pitch', 'hit', 'out', 'walk', 'hbp', 'error', 'substitution', 'bunt', 'balk', 'inning_end', 'game_end', 'runner_movement']);
            $table->string('subtype', 30)->nullable(); // looking/swinging/foul_tip, fly/line/ground, single/double/triple/hr, etc.
            $table->string('result', 60)->nullable();  // descripcion corta (e.g., "6-3 groundout", "single to LF")

            // Jugadores involucrados
            $table->foreignId('batter_id')->nullable()->constrained('athletes')->nullOnDelete();
            $table->foreignId('pitcher_id')->nullable()->constrained('athletes')->nullOnDelete();

            // Estado del juego antes y despues de la jugada (snapshot)
            $table->unsignedTinyInteger('outs_before')->default(0);
            $table->unsignedTinyInteger('outs_after')->default(0);
            $table->json('bases_before')->nullable();   // {first: athlete_id, second: ..., third: ...}
            $table->json('bases_after')->nullable();
            $table->unsignedTinyInteger('balls')->default(0);
            $table->unsignedTinyInteger('strikes')->default(0);

            // Resultado en carreras
            $table->unsignedTinyInteger('runs_scored')->default(0);
            $table->unsignedTinyInteger('rbi')->default(0);
            $table->json('runs_detail')->nullable();  // [{athlete_id, scored_from: 'second'}]

            // Metadata especifica del tipo
            $table->json('meta')->nullable();

            // Audit
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at')->useCurrent();

            $table->timestamps();

            $table->index(['game_id', 'inning', 'half', 'sequence']);
            $table->index(['game_id', 'type']);
            $table->index(['game_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plays');
    }
};

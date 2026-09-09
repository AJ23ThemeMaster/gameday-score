<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_athlete', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained('athletes')->restrictOnDelete();
            $table->foreignId('team_id')->constrained('teams')->restrictOnDelete();
            $table->unsignedTinyInteger('lineup_order')->nullable();
            $table->string('position', 5)->nullable();
            $table->boolean('is_starter')->default(true);
            $table->boolean('is_pitcher')->default(false)->index();
            $table->unsignedSmallInteger('pitches_thrown')->default(0);
            $table->unsignedSmallInteger('at_bats')->default(0);
            $table->unsignedSmallInteger('hits')->default(0);
            $table->unsignedSmallInteger('runs')->default(0);
            $table->unsignedSmallInteger('rbi')->default(0);
            $table->timestamps();

            $table->unique(['game_id', 'athlete_id']);
            $table->index(['game_id', 'team_id', 'is_starter']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_athlete');
    }
};

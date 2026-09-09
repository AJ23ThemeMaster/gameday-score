<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_scorekeeper', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->foreignId('scorekeeper_id')->constrained('scorekeepers')->restrictOnDelete();
            $table->string('role', 50)->default('principal')->comment('principal|auxiliar|planilla');
            $table->timestamps();

            $table->unique(['game_id', 'scorekeeper_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_scorekeeper');
    }
};

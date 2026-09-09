<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_referee', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->foreignId('referee_id')->constrained('referees')->restrictOnDelete();
            $table->string('role', 50)->default('home_plate')
                ->comment('home_plate|first_base|second_base|third_base|left_field|right_field');
            $table->timestamps();

            $table->unique(['game_id', 'referee_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_referee');
    }
};

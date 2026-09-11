<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained('leagues')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('logo_path')->nullable();
            $table->string('category', 80)->nullable()
                ->comment('Categoria del torneo: Pre-juvenil, Sub-12, Pony, etc.');
            $table->string('season', 30)->nullable()
                ->comment('Temporada: 2025-A, 2025-B, etc.');
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->unique(['league_id', 'slug']);
            $table->index('name');
            $table->index(['league_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};

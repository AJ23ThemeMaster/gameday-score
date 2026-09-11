<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // DISI-14: 1 categoria pertenece a 1 equipo. El games.category_id
        // existente queda como "la categoria del home_team para este juego".
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->after('id')
                ->constrained('teams')->nullOnDelete();
            $table->index('team_id');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
        });
    }
};

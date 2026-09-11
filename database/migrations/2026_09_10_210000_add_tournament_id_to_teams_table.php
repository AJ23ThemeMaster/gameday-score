<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // DISI-14: 1 equipo pertenece a 1 torneo (nullable para no romper los
        // equipos ya creados antes de esta migracion).
        Schema::table('teams', function (Blueprint $table) {
            $table->foreignId('tournament_id')->nullable()->after('id')
                ->constrained('tournaments')->nullOnDelete();
            $table->index('tournament_id');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tournament_id');
        });
    }
};

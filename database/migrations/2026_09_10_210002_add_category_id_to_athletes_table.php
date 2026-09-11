<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // DISI-14: 1 atleta pertenece a 1 categoria (nullable para atletas
        // legacy). Mantenemos team_id (que ya tenia).
        Schema::table('athletes', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('team_id')
                ->constrained('categories')->nullOnDelete();
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('athletes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });
    }
};

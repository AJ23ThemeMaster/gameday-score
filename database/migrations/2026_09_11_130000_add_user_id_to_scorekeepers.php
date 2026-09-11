<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // DISI-15: vincular un Scorekeeper con un User (anotador) para
        // controlar quien puede anotar cada juego.
        Schema::table('scorekeepers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')
                ->constrained('users')->nullOnDelete();
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('scorekeepers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};

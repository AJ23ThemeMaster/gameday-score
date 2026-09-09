<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('athletes', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 100);
            $table->string('last_name', 100)->index();
            $table->string('document_id', 30)->nullable()->unique();
            $table->date('birth_date')->nullable();
            $table->string('photo_path')->nullable();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->unsignedTinyInteger('number')->nullable();
            $table->enum('position', ['P', 'C', '1B', '2B', '3B', 'SS', 'LF', 'CF', 'RF', 'DH'])->nullable();
            $table->enum('bats', ['L', 'R', 'S'])->default('R')->comment('L=left, R=right, S=switch');
            $table->enum('throws', ['L', 'R'])->default('R')->comment('L=left, R=right');
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('athletes');
    }
};

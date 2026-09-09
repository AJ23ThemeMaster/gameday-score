<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stadiums', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('city', 100)->nullable()->index();
            $table->string('state', 100)->nullable();
            $table->string('address', 255)->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stadiums');
    }
};

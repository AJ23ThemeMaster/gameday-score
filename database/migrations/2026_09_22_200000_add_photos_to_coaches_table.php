<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega columnas para foto del coach y foto del documento de
 * identidad. Mismo patron que athletes (photo_path + document_file_path)
 * pero con nombres especificos para coaches.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('coaches', function (Blueprint $table) {
            // Foto del rostro del coach (para mostrar en fichas y listados).
            $table->string('photo_path')->nullable()->after('notes');
            // Foto del documento de identidad (cedula, DNI, etc).
            $table->string('document_photo_path')->nullable()->after('photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('coaches', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'document_photo_path']);
        });
    }
};

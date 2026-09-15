<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DISI-63: agrega campo para subir el documento del atleta (acta de
     * nacimiento o cedula de identidad). Acepta imagen (jpg/png/webp) o PDF,
     * se guarda en el disk 'public' bajo 'athletes/documents/{id}/...'.
     *
     * Nullable: atletas existentes no tendran documento hasta que se suba.
     */
    public function up(): void
    {
        Schema::table('athletes', function (Blueprint $table) {
            $table->string('document_file_path')->nullable()->after('photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('athletes', function (Blueprint $table) {
            $table->dropColumn('document_file_path');
        });
    }
};

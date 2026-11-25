<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Berita Acara otomatis: unique constraint kombinasi sumber agar tidak
 * terjadi duplikasi saat status trip diperbarui berulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('berita_acaras', function (Blueprint $table) {
            $table->string('source_type')->nullable()->after('id');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');

            // Kolom lama tetap dipertahankan untuk kompatibilitas PDF lama.
            $table->unique(['source_type', 'source_id'], 'berita_acaras_source_unique');
        });
    }

    public function down(): void
    {
        Schema::table('berita_acaras', function (Blueprint $table) {
            $table->dropUnique('berita_acaras_source_unique');
            $table->dropColumn(['source_type', 'source_id']);
        });
    }
};

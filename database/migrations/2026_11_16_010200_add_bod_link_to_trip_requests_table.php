<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Trip BOD dapat menyertakan tautan referensi (Google Maps titik jemput,
     * agenda, dokumen eksternal, dsb).
     */
    public function up(): void
    {
        Schema::table('trip_requests', function (Blueprint $table) {
            $table->string('bod_link', 2048)->nullable()->after('guest_institution');
        });
    }

    public function down(): void
    {
        Schema::table('trip_requests', function (Blueprint $table) {
            $table->dropColumn('bod_link');
        });
    }
};

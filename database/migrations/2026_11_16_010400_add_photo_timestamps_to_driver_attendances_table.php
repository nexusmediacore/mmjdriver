<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Menambahkan kolom photo_timestamp (clock_in_photo_timestamp dan clock_out_photo_timestamp)
     * untuk mencatat waktu penanda (timestamp) yang dibakar / di-overlay ke atas foto selfie absensi.
     */
    public function up(): void
    {
        Schema::table('driver_attendances', function (Blueprint $table) {
            $table->dateTime('clock_in_photo_timestamp')->nullable()->after('clock_in_photo_path');
            $table->dateTime('clock_out_photo_timestamp')->nullable()->after('clock_out_photo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('driver_attendances', function (Blueprint $table) {
            $table->dropColumn(['clock_in_photo_timestamp', 'clock_out_photo_timestamp']);
        });
    }
};

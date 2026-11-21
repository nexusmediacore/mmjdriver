<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu trip dapat memiliki banyak driver (khusus Purchasing dengan bantuan
     * loading): driver utama (MAIN) membawa kendaraan, driver lain berperan
     * sebagai pendukung loading (LOADING_SUPPORT).
     */
    public function up(): void
    {
        Schema::table('trip_assignments', function (Blueprint $table) {
            $table->string('role', 30)->default('MAIN')->after('vehicle_id');
            $table->index(['trip_request_id', 'driver_id']);
        });
    }

    public function down(): void
    {
        // MariaDB memakai index composite untuk FK trip_request_id —
        // FK harus dilepas sebelum index.
        try {
            Schema::table('trip_assignments', function (Blueprint $table) {
                $table->dropForeign(['trip_request_id']);
            });
        } catch (Throwable) {
            // FK sudah tidak ada.
        }

        Schema::table('trip_assignments', function (Blueprint $table) {
            $table->dropIndex(['trip_request_id', 'driver_id']);
            $table->dropColumn('role');
        });

        // Kembalikan FK yang hilang.
        Schema::table('trip_assignments', function (Blueprint $table) {
            $table->foreign('trip_request_id')
                ->references('id')
                ->on('trip_requests')
                ->onDelete('cascade');
        });
    }
};

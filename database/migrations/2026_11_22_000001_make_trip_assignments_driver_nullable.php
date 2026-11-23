<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_assignments', function (Blueprint $table) {
            // Izinkan driver dihapus tanpa menghilangkan riwayat penugasan:
            // FK berganti menjadi SET NULL dan kolom menjadi nullable.
            $table->dropForeign(['driver_id']);
            $table->unsignedBigInteger('driver_id')->nullable()->change();
            $table->foreign('driver_id')
                ->references('id')
                ->on('drivers')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('trip_assignments', function (Blueprint $table) {
            $table->dropForeign(['driver_id']);
            $table->foreignId('driver_id')->change();
            $table->foreign('driver_id')
                ->references('id')
                ->on('drivers')
                ->onDelete('restrict');
        });
    }
};

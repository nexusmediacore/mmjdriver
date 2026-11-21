<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Trip BOD dapat ditugaskan driver tanpa kendaraan —
     * kendaraan diisi belakangan oleh dispatcher.
     */
    public function up(): void
    {
        Schema::table('trip_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('vehicle_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('trip_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('vehicle_id')->nullable(false)->change();
        });
    }
};

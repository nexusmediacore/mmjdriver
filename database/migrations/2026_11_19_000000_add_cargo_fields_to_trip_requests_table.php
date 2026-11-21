<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom untuk jenis permintaan CARGO_PICKUP (request muat barang):
 * - cargo_pickup_location : lokasi barang akan dimuat
 * - cargo_description     : deskripsi barang yang dimuat
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_requests', function (Blueprint $table) {
            $table->string('cargo_pickup_location')->nullable()->after('guest_institution');
            $table->string('cargo_description')->nullable()->after('cargo_pickup_location');
        });
    }

    public function down(): void
    {
        Schema::table('trip_requests', function (Blueprint $table) {
            $table->dropColumn(['cargo_pickup_location', 'cargo_description']);
        });
    }
};

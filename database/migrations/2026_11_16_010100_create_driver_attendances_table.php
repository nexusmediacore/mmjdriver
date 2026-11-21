<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Absensi driver (check-in / check-out) dengan foto selfie.
     * Satu record per driver per tanggal.
     */
    public function up(): void
    {
        Schema::create('driver_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->date('date');

            $table->dateTime('clock_in_at')->nullable();
            $table->string('clock_in_photo_path')->nullable();
            $table->decimal('clock_in_lat', 10, 7)->nullable();
            $table->decimal('clock_in_lng', 10, 7)->nullable();

            $table->dateTime('clock_out_at')->nullable();
            $table->string('clock_out_photo_path')->nullable();
            $table->decimal('clock_out_lat', 10, 7)->nullable();
            $table->decimal('clock_out_lng', 10, 7)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['driver_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_attendances');
    }
};

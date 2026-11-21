<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('berita_acaras', function (Blueprint $table) {
            $table->id();
            $table->string('ba_number')->unique();
            $table->foreignId('trip_request_id')->constrained('trip_requests')->onDelete('cascade');
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->onDelete('set null');
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->onDelete('set null');
            $table->string('requestor_name');
            $table->string('department_name');
            $table->string('destination');
            $table->text('purpose');
            $table->dateTime('departure_time');
            $table->dateTime('return_time')->nullable();
            $table->text('notes')->nullable();
            $table->string('signed_by')->nullable();
            $table->string('signed_position')->nullable();
            $table->dateTime('signed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berita_acaras');
    }
};

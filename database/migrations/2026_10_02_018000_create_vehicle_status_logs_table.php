<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->enum('from_status', ['AVAILABLE', 'ASSIGNED', 'ON_TRIP', 'MAINTENANCE', 'OUT_OF_SERVICE']);
            $table->enum('to_status', ['AVAILABLE', 'ASSIGNED', 'ON_TRIP', 'MAINTENANCE', 'OUT_OF_SERVICE']);
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_status_logs');
    }
};

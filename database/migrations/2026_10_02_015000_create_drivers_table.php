<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->string('license_number')->unique();
            $table->string('vehicle_plate')->nullable();
            $table->string('vehicle_type')->nullable();
            $table->string('phone', 20);
            $table->enum('status', ['STANDBY', 'ASSIGNED', 'ACCEPTED', 'ON_TRIP', 'RETURNING', 'COMPLETED', 'OFF_DUTY', 'LEAVE', 'SICK', 'UNAVAILABLE'])->default('STANDBY');
            $table->boolean('willing_to_load')->default(false);
            $table->text('notes')->nullable();
            $table->timestamp('last_status_change_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};

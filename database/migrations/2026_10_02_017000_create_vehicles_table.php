<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number')->unique();
            $table->string('brand');
            $table->string('model');
            $table->string('type');
            $table->integer('year');
            $table->integer('passenger_capacity');
            $table->enum('status', ['AVAILABLE', 'ASSIGNED', 'ON_TRIP', 'MAINTENANCE', 'OUT_OF_SERVICE'])->default('AVAILABLE');
            $table->foreignId('primary_driver_id')->nullable()->constrained('drivers')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->date('last_service_date')->nullable();
            $table->date('next_service_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};

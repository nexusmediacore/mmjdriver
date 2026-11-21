<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loading_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trip_request_id');
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->enum('status', ['PENDING', 'ACCEPTED', 'COMPLETED', 'CANCELLED'])->default('PENDING');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Foreign keys added separately to avoid MariaDB errno 150
        Schema::table('loading_requests', function (Blueprint $table) {
            $table->foreign('trip_request_id')
                ->references('id')
                ->on('trip_requests')
                ->onDelete('cascade');
        });

        Schema::table('loading_requests', function (Blueprint $table) {
            $table->foreign('driver_id')
                ->references('id')
                ->on('drivers')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loading_requests');
    }
};

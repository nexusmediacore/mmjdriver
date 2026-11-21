<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_requests', function (Blueprint $table) {
            $table->foreignId('assigned_driver_id')->nullable()->constrained('drivers')->onDelete('set null');
            $table->foreignId('assigned_vehicle_id')->nullable()->constrained('vehicles')->onDelete('set null');
            $table->dateTime('started_at')->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('completion_notes')->nullable();
            $table->dateTime('actual_return_time')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('trip_requests', function (Blueprint $table) {
            $table->dropColumn([
                'assigned_driver_id', 'assigned_vehicle_id',
                'started_at', 'started_by', 'completed_at', 'completed_by',
                'completion_notes', 'actual_return_time',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_request_id')->constrained('trip_requests')->onDelete('cascade');
            $table->foreignId('approver_id')->constrained('users')->onDelete('restrict');
            $table->enum('decision', ['APPROVE', 'REJECT']);
            $table->text('notes')->nullable();
            $table->dateTime('decided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('trip_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_request_id')->constrained('trip_requests')->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('restrict');
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('restrict');
            $table->foreignId('assigned_by')->constrained('users')->onDelete('restrict');
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('driver_accepted_at')->nullable();
            $table->text('driver_rejection_reason')->nullable();
            $table->enum('driver_response', ['PENDING', 'ACCEPTED', 'REJECTED'])->default('PENDING');
            $table->timestamps();
        });

        Schema::create('trip_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_request_id')->constrained('trip_requests')->onDelete('cascade');
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);
            $table->foreignId('changed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_status_logs');
        Schema::dropIfExists('trip_assignments');
        Schema::dropIfExists('trip_approvals');
    }
};

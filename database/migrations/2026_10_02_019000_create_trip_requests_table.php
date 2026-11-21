<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_requests', function (Blueprint $table) {
            $table->id();
            $table->string('trip_number')->unique();
            $table->foreignId('requestor_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('department_id')->constrained('departments')->onDelete('restrict');
            $table->date('trip_date')->nullable();
            $table->dateTime('departure_time');
            $table->dateTime('estimated_return_time');
            $table->string('destination');
            $table->text('destination_address')->nullable();
            $table->text('purpose');
            $table->integer('passenger_count')->default(1);
            $table->enum('priority', ['NORMAL', 'URGENT', 'VERY_URGENT'])->default('NORMAL');
            $table->text('notes')->nullable();
            $table->boolean('requires_loading_assistance')->default(false);
            $table->enum('status', [
                'DRAFT', 'SUBMITTED', 'WAITING_APPROVAL', 'APPROVED', 'REJECTED',
                'WAITING_DRIVER', 'DRIVER_ASSIGNED', 'DRIVER_ACCEPTED', 'DRIVER_REJECTED',
                'READY_TO_DEPART', 'ON_TRIP', 'COMPLETED', 'CANCELLED',
            ])->default('DRAFT');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('approval_notes')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('trip_passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_request_id')->constrained('trip_requests')->onDelete('cascade');
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('department')->nullable();
            $table->string('nik', 16)->nullable();
            $table->string('position')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_passengers');
        Schema::dropIfExists('trip_requests');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengajuan selesai perjalanan (dual trigger: driver & pemohon) membutuhkan
     * 1x approval Admin/SuperAdmin sebelum status benar-benar COMPLETED.
     */
    public function up(): void
    {
        Schema::table('trip_requests', function (Blueprint $table) {
            // Kolom status sengaja bertipe string (bukan enum) agar status baru
            // dapat ditambahkan tanpa mengubah struktur enum MySQL.
            $table->string('status', 50)->default('DRAFT')->change();

            $table->dateTime('completion_submitted_at')->nullable()->after('completion_notes');
            $table->foreignId('completion_submitted_by')->nullable()->after('completion_submitted_at')
                ->constrained('users')->onDelete('set null');
            $table->dateTime('completion_approved_at')->nullable()->after('completion_submitted_by');
            $table->foreignId('completion_approved_by')->nullable()->after('completion_approved_at')
                ->constrained('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('trip_requests', function (Blueprint $table) {
            $table->dropForeign(['completion_submitted_by']);
            $table->dropForeign(['completion_approved_by']);
        });

        Schema::table('trip_requests', function (Blueprint $table) {
            $table->dropColumn([
                'completion_submitted_at', 'completion_submitted_by',
                'completion_approved_at', 'completion_approved_by',
            ]);
        });

        // Kembalikan tipe enum (SQLite tidak mendukung ENUM, jadi dilewati).
        if (DB::getDriverName() === 'mysql') {
            Schema::table('trip_requests', function (Blueprint $table) {
                $table->enum('status', [
                    'DRAFT', 'SUBMITTED', 'WAITING_APPROVAL', 'APPROVED', 'REJECTED',
                    'WAITING_DRIVER', 'DRIVER_ASSIGNED', 'DRIVER_ACCEPTED', 'DRIVER_REJECTED',
                    'READY_TO_DEPART', 'ON_TRIP', 'COMPLETED', 'CANCELLED',
                ])->default('DRAFT')->change();
            });
        }
    }
};

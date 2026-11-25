<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Skema mode "muat barang" (Purchasing):
 * - Kolom informasi perjalanan dibuat nullable (tanpa driver utama).
 * - Status approval Superadmin untuk driver bantu muat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_requests', function (Blueprint $table) {
            // Informasi perjalanan menjadi opsional saat mode muat barang.
            $table->dateTime('departure_time')->nullable()->change();
            $table->string('destination')->nullable()->change();
            $table->text('purpose')->nullable()->change();

            // Persetujuan Superadmin atas pemilihan driver bantu muat.
            $table->enum('loading_approval_status', ['NONE', 'PENDING', 'APPROVED', 'REJECTED'])
                ->default('NONE');
            $table->foreignId('loading_approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('loading_approved_at')->nullable();
            $table->text('loading_approval_note')->nullable();
        });

        Schema::table('loading_requests', function (Blueprint $table) {
            $table->enum('approval_status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('approved_at')->nullable();
            $table->text('approval_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('trip_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('loading_approved_by');
            $table->dropColumn(['loading_approval_status', 'loading_approved_at', 'loading_approval_note']);
        });

        Schema::table('loading_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['approval_status', 'approved_at', 'approval_note']);
        });
    }
};

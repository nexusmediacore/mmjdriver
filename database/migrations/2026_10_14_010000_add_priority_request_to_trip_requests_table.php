<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_requests', function (Blueprint $table) {
            // Prioritas yang diajukan admin departemen (belum final)
            $table->string('requested_priority', 20)->nullable()->after('priority');
            // Alasan kenapa harus urgent / very urgent (dari admin departemen)
            $table->text('urgency_notes')->nullable()->after('requested_priority');
            // Prioritas final yang ditetapkan SuperAdmin saat approve
            $table->string('priority_set_by', 20)->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('trip_requests', function (Blueprint $table) {
            $table->dropColumn(['requested_priority', 'urgency_notes', 'priority_set_by']);
        });
    }
};

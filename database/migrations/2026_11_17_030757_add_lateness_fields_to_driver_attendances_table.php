<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('driver_attendances', function (Blueprint $table) {
            $table->boolean('is_late')->default(false)->after('clock_in_lng');
            $table->unsignedInteger('lateness_minutes')->default(0)->after('is_late');
            $table->decimal('clock_in_distance_meters', 8, 2)->nullable()->after('lateness_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('driver_attendances', function (Blueprint $table) {
            $table->dropColumn(['is_late', 'lateness_minutes', 'clock_in_distance_meters']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trip_requests', function (Blueprint $table) {
            $table->string('request_type', 30)->default('BUSINESS_TRIP')->after('id');
            $table->string('guest_pickup_location')->nullable()->after('destination_address');
            $table->string('guest_institution')->nullable()->after('guest_pickup_location');
            $table->index('request_type');
        });
    }

    public function down(): void
    {
        Schema::table('trip_requests', function (Blueprint $table) {
            $table->dropIndex(['request_type']);
            $table->dropColumn(['request_type', 'guest_pickup_location', 'guest_institution']);
        });
    }
};

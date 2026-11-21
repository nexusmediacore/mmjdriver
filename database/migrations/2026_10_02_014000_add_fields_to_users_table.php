<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('set null');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->string('phone', 20)->nullable();
            $table->string('avatar')->nullable();
            $table->enum('gender', ['L', 'P'])->nullable(); // Laki-laki, Perempuan
            $table->date('birth_date')->nullable();
            $table->string('nik', 16)->unique()->nullable();
            $table->string('npwp', 15)->unique()->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role_id', 'department_id', 'phone', 'avatar', 'gender', 'birth_date', 'nik', 'npwp', 'is_active', 'last_login_at']);
        });
    }
};

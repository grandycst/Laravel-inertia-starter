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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            // Nullable: akun yang hanya pernah login lewat Google tidak wajib punya password
            // (docs/SRS.md FR-1.2, docs/DESIGN.md §3.1).
            $table->string('password')->nullable();
            $table->string('google_id')->nullable()->unique();
            $table->string('avatar_path')->nullable();
            $table->string('locale', 5)->nullable();
            // FK ke employees ditambahkan di migrasi 2026_07_23_100002 setelah tabel employees ada
            // (HRD/Admin non-pegawai boleh tidak punya baris employee, docs/DESIGN.md §3.1).
            $table->foreignId('employee_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};

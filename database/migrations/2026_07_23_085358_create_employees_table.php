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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nip')->unique();
            $table->string('nama');
            $table->date('tanggal_lahir')->nullable();
            $table->date('tanggal_bergabung');

            $table->enum('status_kawin', ['belum_kawin', 'kawin', 'cerai'])->nullable();
            $table->unsignedTinyInteger('jumlah_tanggungan')->default(0);

            $table->enum('status_kepegawaian', ['probation', 'tetap', 'kontrak', 'honorer'])->default('probation');
            $table->date('tanggal_mulai_probation')->nullable();
            $table->date('tanggal_akhir_probation')->nullable();

            $table->enum('status_aktif', ['aktif', 'nonaktif'])->default('aktif');
            $table->date('tanggal_resign')->nullable();
            $table->string('alasan_resign')->nullable();

            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            // Atasan langsung — approver pertama untuk izin/lembur, bisa beda dari kepala_unit_id
            // (lihat catatan resolusi atasan di docs/DESIGN.md §3.1).
            $table->foreignId('atasan_id')->nullable()->constrained('employees')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};

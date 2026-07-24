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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('nama_unit');
            $table->foreignId('parent_unit_id')->nullable()->constrained('units')->nullOnDelete();
            // kepala_unit_id ditambahkan di migrasi terpisah setelah tabel employees ada
            // (referensi silang units<->employees, docs/DESIGN.md §3.1).
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};

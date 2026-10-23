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
        Schema::create('sistem_kategori_sub', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sistemkategori_id')->constrained('sistem_kategori')->cascadeOnDelete();
            $table->string('nama');
            $table->string('keterangan')->nullable();
            $table->tinyInteger('is_status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sistem_kategori_sub');
    }
};

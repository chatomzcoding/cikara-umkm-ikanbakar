<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_pokok', function (Blueprint $table) {
            $table->unsignedTinyInteger('singleton_key')->default(1)->unique();
        });
    }

    public function down(): void
    {
        Schema::table('data_pokok', function (Blueprint $table) {
            $table->dropUnique(['singleton_key']);
            $table->dropColumn('singleton_key');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_pokok', function (Blueprint $table): void {
            $table->string('sub_menu_default', 10)->default('hide');
        });
    }

    public function down(): void
    {
        Schema::table('data_pokok', function (Blueprint $table): void {
            $table->dropColumn('sub_menu_default');
        });
    }
};

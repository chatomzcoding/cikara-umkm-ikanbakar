<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_page_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('status_code')->unique();
            $table->boolean('is_active')->default(true);
            $table->string('title');
            $table->text('message');
            $table->string('accent', 20)->default('indigo');
            $table->string('button_label')->nullable();
            $table->string('button_url')->nullable();
            $table->boolean('show_back_button')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_page_settings');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswas', function (Blueprint $table): void {
            $table->ulid('ulid')->nullable()->after('id');
        });

        DB::table('siswas')->whereNull('ulid')->orderBy('id')->chunkById(500, function ($rows): void {
            foreach ($rows as $row) {
                DB::table('siswas')->where('id', $row->id)->update(['ulid' => (string) Str::ulid()]);
            }
        });

        Schema::table('siswas', function (Blueprint $table): void {
            $table->ulid('ulid')->nullable(false)->change();
            $table->unique('ulid');
        });
    }

    public function down(): void
    {
        Schema::table('siswas', function (Blueprint $table): void {
            $table->dropUnique(['ulid']);
            $table->dropColumn('ulid');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('status', 20)->default('active')->index()->after('password');
            $table->timestamp('activated_at')->nullable()->after('status');
            $table->timestamp('suspended_at')->nullable()->after('activated_at');
            $table->foreignId('suspended_by')->nullable()->after('suspended_at')->constrained('users')->nullOnDelete();
            $table->text('suspension_reason')->nullable()->after('suspended_by');
            $table->timestamp('deactivated_at')->nullable()->after('suspension_reason');
            $table->foreignId('deactivated_by')->nullable()->after('deactivated_at')->constrained('users')->nullOnDelete();
            $table->text('deactivation_reason')->nullable()->after('deactivated_by');
        });

        DB::table('users')->update([
            'activated_at' => DB::raw('COALESCE(email_verified_at, created_at)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('suspended_by');
            $table->dropConstrainedForeignId('deactivated_by');
            $table->dropIndex(['status']);
            $table->dropColumn([
                'status',
                'activated_at',
                'suspended_at',
                'suspension_reason',
                'deactivated_at',
                'deactivation_reason',
            ]);
        });
    }
};

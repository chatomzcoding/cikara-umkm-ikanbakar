<?php

namespace Database\Seeders;

use App\Models\ErrorPageSetting;
use App\Services\ErrorPageManager;
use Illuminate\Database\Seeder;

class ErrorPageSettingSeeder extends Seeder
{
    public function run(): void
    {
        foreach (ErrorPageManager::defaults() as $statusCode => $settings) {
            ErrorPageSetting::query()->firstOrCreate(
                ['status_code' => $statusCode],
                ['is_active' => true, ...$settings],
            );
        }
    }
}

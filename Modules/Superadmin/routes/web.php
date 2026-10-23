<?php

use App\Services\ErrorPageManager;
use Illuminate\Support\Facades\Route;
use Modules\Superadmin\Http\Controllers\AuditLogController;
use Modules\Superadmin\Http\Controllers\DataPokokController;
use Modules\Superadmin\Http\Controllers\ErrorPageController;
use Modules\Superadmin\Http\Controllers\RoleController;
use Modules\Superadmin\Http\Controllers\SistemkategoriController;
use Modules\Superadmin\Http\Controllers\SuperadminController;
use Modules\Superadmin\Http\Controllers\UserController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('superadmin')->name('superadmin.')->group(function () {
        Route::get('/', [SuperadminController::class, 'index'])
            ->middleware('can:dashboard.view')
            ->name('dashboard');

        Route::prefix('sistem')->name('sistem.')->group(function () {
            Route::get('data-pokok', [DataPokokController::class, 'index'])
                ->middleware('can:data-pokok.view')
                ->name('data-pokok.index');
            Route::get('kategori', [SistemkategoriController::class, 'index'])
                ->middleware('can:categories.view')
                ->name('kategori.index');
            Route::get('kategori/{kategori}', [SistemkategoriController::class, 'show'])
                ->middleware('can:categories.view')
                ->name('kategori.show');
            Route::get('user', [UserController::class, 'index'])
                ->middleware('can:users.view')
                ->name('user.index');
            Route::get('role', [RoleController::class, 'index'])
                ->middleware('can:roles.view')
                ->name('role.index');
            Route::get('audit-log', [AuditLogController::class, 'index'])
                ->middleware('can:audit-logs.view')
                ->name('audit-log.index');
            Route::get('error-page', [ErrorPageController::class, 'index'])
                ->middleware('can:error-pages.view')
                ->name('error-page.index');
            Route::get('error-page/{statusCode}/preview', [ErrorPageController::class, 'preview'])
                ->middleware('can:error-pages.view')
                ->whereIn('statusCode', array_map('strval', ErrorPageManager::SUPPORTED_CODES))
                ->name('error-page.preview');
        });
    });
});

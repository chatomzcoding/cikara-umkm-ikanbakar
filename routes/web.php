<?php

use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserImpersonationController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [HomeController::class, 'index'])->name('dashboard');
    Route::get('search', [GlobalSearchController::class, 'index'])->name('global-search.index');
    Route::post('search/recent', [GlobalSearchController::class, 'remember'])->name('global-search.remember');
    Route::get('search/results', [GlobalSearchController::class, 'results'])->name('global-search.results');
    Route::patch('notifications/read-all', [NotificationController::class, 'readAll'])
        ->name('notifications.read-all');
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'read'])
        ->name('notifications.read');
});

Route::middleware('auth')->post('impersonation/stop', [UserImpersonationController::class, 'destroy'])
    ->name('impersonation.stop');

require __DIR__.'/settings.php';

// CRUD-GENERATOR:LOAD-ROUTES
foreach (glob(__DIR__.'/crud/*.php') ?: [] as $crudRouteFile) {
    require $crudRouteFile;
}

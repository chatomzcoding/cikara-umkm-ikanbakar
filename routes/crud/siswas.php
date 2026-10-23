<?php

use App\Http\Controllers\SiswaController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('siswas', [SiswaController::class, 'index'])
        ->middleware('can:siswas.view')
        ->name('siswas.index');
});

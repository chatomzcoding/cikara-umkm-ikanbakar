<?php

use Illuminate\Support\Facades\Route;
use Modules\Data\Http\Controllers\SiswaController;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('data/siswas', [SiswaController::class, 'index'])
        ->middleware('can:data.siswas.view')
        ->name('data.siswas.index');
});

<?php

declare(strict_types=1);

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Health\HealthEntryController;
use App\Http\Controllers\Health\HealthExportController;
use App\Http\Controllers\Health\HealthStatsController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::prefix('health')->name('health.')->group(function () {
    Route::get('/', [HealthEntryController::class,  'index'])->name('index');
    Route::post('/', [HealthEntryController::class,  'store'])->name('store');
    Route::patch('/{entry}', [HealthEntryController::class,  'update'])->name('update');
    Route::delete('/{entry}', [HealthEntryController::class,  'destroy'])->name('destroy');
    Route::get('/stats', [HealthStatsController::class,  'index'])->name('stats');
    Route::get('/export', [HealthExportController::class, 'index'])->name('export');
});

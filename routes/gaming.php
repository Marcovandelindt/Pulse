<?php

declare(strict_types=1);

use App\Http\Controllers\Gaming\PlayStationController;
use Illuminate\Support\Facades\Route;

Route::prefix('playstation')->name('playstation.')->group(function () {
    Route::get('/', [PlayStationController::class, 'index'])->name('index');
    Route::get('/create', [PlayStationController::class, 'create'])->name('create');
    Route::post('/', [PlayStationController::class, 'store'])->name('store');
    Route::get('/sessions', [PlayStationController::class, 'sessions'])->name('sessions');
    Route::post('/sync', [PlayStationController::class, 'sync'])->name('sync');
    Route::get('/daily-activity', [PlayStationController::class, 'dailyActivity'])->name('daily-activity');
    Route::get('/{playStationGame}', [PlayStationController::class, 'show'])->name('show');
    Route::get('/{playStationGame}/edit', [PlayStationController::class, 'edit'])->name('edit');
    Route::patch('/{playStationGame}', [PlayStationController::class, 'update'])->name('update');
});

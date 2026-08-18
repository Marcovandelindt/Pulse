<?php

declare(strict_types=1);

use App\Http\Controllers\Gaming\BacklogController;
use App\Http\Controllers\Gaming\PlayStationCategoryController;
use App\Http\Controllers\Gaming\PlayStationController;
use App\Http\Controllers\Gaming\SteamController;
use Illuminate\Support\Facades\Route;

Route::prefix('playstation')->name('playstation.')->group(function () {
    Route::get('/', [PlayStationController::class, 'index'])->name('index');
    Route::get('/create', [PlayStationController::class, 'create'])->name('create');
    Route::post('/', [PlayStationController::class, 'store'])->name('store');
    Route::get('/sessions', [PlayStationController::class, 'sessions'])->name('sessions');
    Route::post('/sync', [PlayStationController::class, 'sync'])->name('sync');
    Route::get('/daily-activity', [PlayStationController::class, 'dailyActivity'])->name('daily-activity');

    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [PlayStationCategoryController::class, 'index'])->name('index');
        Route::post('/', [PlayStationCategoryController::class, 'store'])->name('store');
        Route::delete('/{playStationCategory}', [PlayStationCategoryController::class, 'destroy'])->name('destroy');
    });

    Route::patch('/trophies/{playStationTrophy}/toggle', [PlayStationController::class, 'toggleTrophy'])->name('trophy.toggle');

    Route::get('/{playStationGame}', [PlayStationController::class, 'show'])->name('show');
    Route::get('/{playStationGame}/edit', [PlayStationController::class, 'edit'])->name('edit');
    Route::patch('/{playStationGame}', [PlayStationController::class, 'update'])->name('update');
    Route::patch('/{playStationGame}/favorite', [PlayStationController::class, 'favorite'])->name('favorite');
    Route::post('/{playStationGame}/fetch-trophies', [PlayStationController::class, 'fetchTrophies'])->name('fetch-trophies');
});

Route::prefix('steam')->name('steam.')->group(function () {
    Route::get('/', [SteamController::class, 'index'])->name('index');
    Route::get('/settings', [SteamController::class, 'settings'])->name('settings');
    Route::post('/sync', [SteamController::class, 'sync'])->name('sync');
    Route::post('/test-connection', [SteamController::class, 'testConnection'])->name('test-connection');
    Route::get('/games/{game}', [SteamController::class, 'show'])->name('games.show');
    Route::get('/games/{game}/edit', [SteamController::class, 'edit'])->name('games.edit');
    Route::put('/games/{game}', [SteamController::class, 'update'])->name('games.update');
});

Route::patch('/gaming/backlog/{type}/{id}/status', [BacklogController::class, 'update'])->name('gaming.backlog.update');

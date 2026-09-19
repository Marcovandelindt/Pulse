<?php

declare(strict_types=1);

use App\Http\Controllers\Gaming\BacklogController;
use App\Http\Controllers\Gaming\NintendoController;
use App\Http\Controllers\Gaming\NintendoImportController;
use App\Http\Controllers\Gaming\NintendoRecordController;
use App\Http\Controllers\Gaming\NintendoSyncController;
use App\Http\Controllers\Gaming\PlayStationCategoryController;
use App\Http\Controllers\Gaming\PlayStationController;
use App\Http\Controllers\Gaming\PlayStationFavoriteController;
use App\Http\Controllers\Gaming\PlayStationSessionController;
use App\Http\Controllers\Gaming\PlayStationRecommendationsController;
use App\Http\Controllers\Gaming\PlayStationWrappedController;
use App\Http\Controllers\Gaming\PlayStationStatsController;
use App\Http\Controllers\Gaming\PlayStationSessionSyncController;
use App\Http\Controllers\Gaming\PlayStationSyncController;
use App\Http\Controllers\Gaming\PlayStationTrophyController;
use App\Http\Controllers\Gaming\SteamAccountController;
use App\Http\Controllers\Gaming\SteamConnectionController;
use App\Http\Controllers\Gaming\SteamController;
use App\Http\Controllers\Gaming\SteamSyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('playstation')->name('playstation.')->group(function () {
    Route::get('/', [PlayStationController::class, 'index'])->name('index');
    Route::get('/create', [PlayStationController::class, 'create'])->name('create');
    Route::post('/', [PlayStationController::class, 'store'])->name('store');
    Route::post('/sync', [PlayStationSyncController::class, 'store'])->name('sync');
    Route::get('/stats', [PlayStationStatsController::class, 'index'])->name('stats');
    Route::get('/wrapped', [PlayStationWrappedController::class, 'index'])->name('wrapped');
    Route::get('/play-next', [PlayStationRecommendationsController::class, 'index'])->name('play-next');

    Route::prefix('sessions')->name('sessions.')->group(function () {
        Route::get('/', [PlayStationSessionController::class, 'index'])->name('index');
        Route::get('/daily', [PlayStationSessionController::class, 'daily'])->name('daily');
        Route::post('/sync', [PlayStationSessionSyncController::class, 'store'])->name('sync');
    });

    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [PlayStationCategoryController::class, 'index'])->name('index');
        Route::post('/', [PlayStationCategoryController::class, 'store'])->name('store');
        Route::delete('/{playStationCategory}', [PlayStationCategoryController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('trophies')->name('trophies.')->group(function () {
        Route::patch('/{playStationTrophy}/toggle', [PlayStationTrophyController::class, 'toggle'])->name('toggle');
    });

    Route::get('/{playStationGame}', [PlayStationController::class, 'show'])->name('show');
    Route::get('/{playStationGame}/edit', [PlayStationController::class, 'edit'])->name('edit');
    Route::patch('/{playStationGame}', [PlayStationController::class, 'update'])->name('update');
    Route::post('/{playStationGame}/fetch-trophies', [PlayStationTrophyController::class, 'fetch'])->name('fetch-trophies');
    Route::patch('/{playStationGame}/favorite', [PlayStationFavoriteController::class, 'toggle'])->name('favorite');
});

Route::prefix('steam')->name('steam.')->group(function () {
    Route::get('/', [SteamController::class, 'index'])->name('index');
    Route::post('/sync', [SteamSyncController::class, 'store'])->name('sync');
    Route::post('/test-connection', [SteamConnectionController::class, 'store'])->name('test-connection');
    Route::get('/settings', [SteamAccountController::class, 'index'])->name('settings');
    Route::get('/games/{game}', [SteamController::class, 'show'])->name('games.show');
    Route::get('/games/{game}/edit', [SteamController::class, 'edit'])->name('games.edit');
    Route::put('/games/{game}', [SteamController::class, 'update'])->name('games.update');

    Route::post('/accounts', [SteamAccountController::class, 'store'])->name('accounts.store');
    Route::post('/accounts/{account}/activate', [SteamAccountController::class, 'activate'])->name('accounts.activate');
    Route::delete('/accounts/{account}', [SteamAccountController::class, 'destroy'])->name('accounts.destroy');
});

Route::prefix('nintendo')->name('nintendo.')->group(function () {
    Route::get('/',        [NintendoController::class, 'index'])->name('index');
    Route::get('/create',  [NintendoController::class, 'create'])->name('create');
    Route::post('/',       [NintendoController::class, 'store'])->name('store');
    Route::get('/search',   [NintendoController::class, 'search'])->name('search');
    Route::get('/sessions', [NintendoRecordController::class, 'index'])->name('sessions');

    Route::get('/import',          [NintendoImportController::class, 'create'])->name('import');
    Route::post('/import',         [NintendoImportController::class, 'store'])->name('import.store');
    Route::get('/import/preview',  [NintendoImportController::class, 'preview'])->name('import.preview');
    Route::post('/import/confirm', [NintendoImportController::class, 'confirm'])->name('import.confirm');

    Route::get('/{game}',          [NintendoController::class, 'show'])->name('show');
    Route::delete('/{game}',       [NintendoController::class, 'destroy'])->name('destroy');

    Route::post('/{game}/records',          [NintendoRecordController::class, 'store'])->name('records.store');
    Route::delete('/{game}/records/{record}', [NintendoRecordController::class, 'destroy'])->name('records.destroy');
});

Route::patch('/gaming/backlog/{type}/{id}/status', [BacklogController::class, 'update'])->name('gaming.backlog.update');

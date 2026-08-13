<?php

declare(strict_types=1);

use App\Http\Controllers\Media\Movies\MovieController;
use App\Http\Controllers\Media\Movies\MovieSearchController;
use App\Http\Controllers\Media\Movies\MovieStatsController;
use App\Http\Controllers\Media\Movies\MovieWatchController;
use App\Http\Controllers\Media\Tv\TvRefreshController;
use App\Http\Controllers\Media\Tv\TvSearchController;
use App\Http\Controllers\Media\Tv\TvSeriesController;
use App\Http\Controllers\Media\Tv\TvStatsController;
use App\Http\Controllers\Media\Tv\TvWatchController;
use App\Http\Controllers\Music\AlbumController;
use App\Http\Controllers\Music\AlbumListenController;
use App\Http\Controllers\Music\AlbumSearchController;
use App\Http\Controllers\Music\AlbumStatsController;
use App\Http\Controllers\Music\ArtistController;
use App\Http\Controllers\PeopleController;
use Illuminate\Support\Facades\Route;

Route::prefix('movies')->name('movies.')->group(function () {
    Route::get('/', [MovieController::class,       'index'])->name('index');
    Route::get('/stats', [MovieStatsController::class,  'index'])->name('stats');
    Route::post('/search', [MovieSearchController::class, 'index'])->name('search');
    Route::post('/', [MovieController::class,       'store'])->name('store');
    Route::get('/{movie}', [MovieController::class,       'show'])->name('show');
    Route::post('/{movie}/watches', [MovieWatchController::class,  'store'])->name('watches.store');
    Route::delete('/watches/{watch}', [MovieWatchController::class,  'destroy'])->name('watches.destroy');
    Route::delete('/{movie}', [MovieController::class,       'destroy'])->name('destroy');
});

Route::prefix('tv')->name('tv.')->group(function () {
    Route::get('/', [TvSeriesController::class, 'index'])->name('index');
    Route::get('/stats', [TvStatsController::class,  'index'])->name('stats');
    Route::post('/search', [TvSearchController::class, 'index'])->name('search');
    Route::post('/', [TvSeriesController::class, 'store'])->name('store');
    Route::get('/{series}', [TvSeriesController::class, 'show'])->name('show');
    Route::patch('/{series}/rating', [TvSeriesController::class, 'rate'])->name('rating');
    Route::patch('/{series}/favorite', [TvSeriesController::class, 'favorite'])->name('favorite');
    Route::post('/episodes/{episode}/watches', [TvWatchController::class,  'store'])->name('episodes.watches.store');
    Route::post('/episodes/{episode}/watches/bulk-up-to', [TvWatchController::class, 'bulkUpTo'])->name('episodes.watches.bulk-up-to');
    Route::post('/{series}/watches/bulk', [TvWatchController::class,  'bulkStore'])->name('watches.bulk');
    Route::post('/{series}/refresh', [TvRefreshController::class, 'store'])->name('refresh');
    Route::delete('/watches/{watch}', [TvWatchController::class,  'destroy'])->name('watches.destroy');
    Route::delete('/{series}', [TvSeriesController::class, 'destroy'])->name('destroy');
});

Route::get('/people/{person}', [PeopleController::class, 'show'])->name('people.show');

Route::prefix('music')->name('music.')->group(function () {
    Route::get('/', [AlbumController::class,       'index'])->name('index');
    Route::get('/stats', [AlbumStatsController::class,  'index'])->name('stats');
    Route::get('/artists/{artist}', [ArtistController::class,      'show'])->name('artists.show');
    Route::post('/search', [AlbumSearchController::class, 'index'])->name('search');
    Route::post('/', [AlbumController::class,       'store'])->name('store');
    Route::get('/{album}', [AlbumController::class,       'show'])->name('show');
    Route::post('/{album}/listens', [AlbumListenController::class, 'store'])->name('listens.store');
    Route::delete('/listens/{listen}', [AlbumListenController::class, 'destroy'])->name('listens.destroy');
    Route::delete('/{album}', [AlbumController::class,       'destroy'])->name('destroy');
});

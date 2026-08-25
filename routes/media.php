<?php

declare(strict_types=1);

use App\Http\Controllers\Media\Movies\MovieController;
use App\Http\Controllers\Media\Movies\MovieSearchController;
use App\Http\Controllers\Media\Movies\MovieStatsController;
use App\Http\Controllers\Media\Movies\MovieWatchController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\Media\Tv\TvCastExclusionController;
use App\Http\Controllers\Media\Tv\TvRefreshController;
use App\Http\Controllers\Media\Tv\TvSearchController;
use App\Http\Controllers\Media\Tv\TvSeriesController;
use App\Http\Controllers\Media\Tv\TvStatsController;
use App\Http\Controllers\Media\Tv\TvWatchController;
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
    Route::post('/{movie}/refresh', [MovieController::class,      'refresh'])->name('refresh');
    Route::post('/{movie}/backdrop', [MovieController::class,     'uploadBackdrop'])->name('backdrop');
    Route::delete('/{movie}', [MovieController::class,            'destroy'])->name('destroy');
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
    Route::post('/seasons/{season}/watches/bulk', [TvWatchController::class, 'bulkStoreSeason'])->name('seasons.watches.bulk');
    Route::post('/{series}/watches/bulk', [TvWatchController::class,  'bulkStore'])->name('watches.bulk');
    Route::post('/{series}/refresh', [TvRefreshController::class,  'store'])->name('refresh');
    Route::post('/{series}/backdrop', [TvSeriesController::class,  'uploadBackdrop'])->name('backdrop');
    Route::delete('/watches/{watch}', [TvWatchController::class,   'destroy'])->name('watches.destroy');
    Route::delete('/{series}', [TvSeriesController::class,         'destroy'])->name('destroy');
    Route::patch('/{series}/cast/{person}/excluded', [TvCastExclusionController::class, 'toggle'])->name('cast.excluded.toggle');
    Route::patch('/{series}/exclude-cast', [TvSeriesController::class, 'toggleExcludeCast'])->name('exclude-cast');
});

Route::get('/search', [GlobalSearchController::class, 'index'])->name('search');

Route::get('/actors', [PeopleController::class, 'index'])->name('actors.index');
Route::get('/actors/{person}', [PeopleController::class, 'show'])->name('actors.show');

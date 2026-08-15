<?php

declare(strict_types=1);

use App\Http\Controllers\RecommendationController;
use Illuminate\Support\Facades\Route;

Route::prefix('recommendations')->name('recommendations.')->group(function () {
    Route::get('/', [RecommendationController::class, 'index'])->name('index');
    Route::get('/actor/{person}', [RecommendationController::class, 'byActor'])->name('actor');
});

<?php

declare(strict_types=1);

use App\Http\Controllers\Location\StoreOverlandBatchController;
use Illuminate\Support\Facades\Route;

Route::post('/location/overland', StoreOverlandBatchController::class)
    ->middleware('overland.token')
    ->name('api.location.overland');

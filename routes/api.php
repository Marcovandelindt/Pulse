<?php

declare(strict_types=1);

use App\Http\Controllers\Location\StoreOwnTracksLocationController;
use Illuminate\Support\Facades\Route;

Route::post('/location/owntracks', StoreOwnTracksLocationController::class)
    ->middleware('owntracks.auth')
    ->name('api.location.owntracks');

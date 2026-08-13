<?php

declare(strict_types=1);

use App\Http\Controllers\Music\AlbumController;
use App\Http\Controllers\Music\ArtistController;
use App\Http\Controllers\Music\MusicDashboardController;
use App\Http\Controllers\Music\MusicStatsController;
use App\Http\Controllers\Music\TrackController;
use App\Http\Controllers\Spotify\SpotifyAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/spotify/auth', [SpotifyAuthController::class, 'redirect'])->name('spotify.auth');
Route::get('/spotify/auth/callback', [SpotifyAuthController::class, 'callback'])->name('spotify.callback');
Route::post('/spotify/disconnect', [SpotifyAuthController::class, 'disconnect'])->name('spotify.disconnect');

Route::prefix('music')->name('music.')->middleware('spotify.connected')->group(function () {
    Route::get('/', [MusicDashboardController::class, 'index'])->name('index');
    Route::get('/stats', [MusicStatsController::class, 'index'])->name('stats');
    Route::get('/tracks/{track}', [TrackController::class, 'show'])->name('tracks.show');
    Route::get('/albums/{album}', [AlbumController::class, 'show'])->name('albums.show');
    Route::get('/artists/{artist}', [ArtistController::class, 'show'])->name('artists.show');
});

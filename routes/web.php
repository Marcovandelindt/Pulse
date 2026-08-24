<?php

declare(strict_types=1);

use App\Http\Controllers\Calendar\CalendarController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Health\HealthEntryController;
use App\Http\Controllers\Health\HealthExportController;
use App\Http\Controllers\Health\HealthStatsController;
use App\Http\Controllers\Health\StepGoalController;
use App\Http\Controllers\People\ContactController;
use App\Http\Controllers\People\ContactDateController;
use App\Http\Controllers\People\ContactRelationshipController;
use App\Http\Controllers\Settings\RelationshipTypeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::prefix('health')->name('health.')->group(function () {
    Route::get('/', [HealthEntryController::class,  'index'])->name('index');
    Route::post('/', [HealthEntryController::class,  'store'])->name('store');
    Route::patch('/{entry}', [HealthEntryController::class,  'update'])->name('update');
    Route::delete('/{entry}', [HealthEntryController::class,  'destroy'])->name('destroy');
    Route::get('/stats', [HealthStatsController::class,   'index'])->name('stats');
    Route::get('/export', [HealthExportController::class, 'index'])->name('export');
    Route::post('/goal', [StepGoalController::class,      'store'])->name('goal.store');
    Route::delete('/goal/{goal}', [StepGoalController::class, 'destroy'])->name('goal.destroy');
});

Route::prefix('calendar')->name('calendar.')->group(function () {
    Route::get('/', [CalendarController::class, 'index'])->name('index');
    Route::post('/', [CalendarController::class, 'store'])->name('store');
    Route::patch('/{event}', [CalendarController::class, 'update'])->name('update');
    Route::delete('/{event}', [CalendarController::class, 'destroy'])->name('destroy');
});

Route::prefix('people')->name('people.')->group(function () {
    Route::get('/', [ContactController::class, 'index'])->name('index');
    Route::get('/create', [ContactController::class, 'create'])->name('create');
    Route::post('/', [ContactController::class, 'store'])->name('store');
    Route::get('/{contact}', [ContactController::class, 'show'])->name('show');
    Route::get('/{contact}/edit', [ContactController::class, 'edit'])->name('edit');
    Route::patch('/{contact}', [ContactController::class, 'update'])->name('update');
    Route::delete('/{contact}', [ContactController::class, 'destroy'])->name('destroy');
    Route::post('/{contact}/dates', [ContactDateController::class, 'store'])->name('dates.store');
    Route::delete('/{contact}/dates/{date}', [ContactDateController::class, 'destroy'])->name('dates.destroy');
    Route::post('/{contact}/relationships', [ContactRelationshipController::class, 'store'])->name('relationships.store');
    Route::delete('/{contact}/relationships/{relationship}', [ContactRelationshipController::class, 'destroy'])->name('relationships.destroy');
});

Route::prefix('settings')->name('settings.')->group(function () {
    Route::prefix('relationships')->name('relationships.')->group(function () {
        Route::get('/', [RelationshipTypeController::class, 'index'])->name('index');
        Route::post('/', [RelationshipTypeController::class, 'store'])->name('store');
        Route::delete('/{relationshipType}', [RelationshipTypeController::class, 'destroy'])->name('destroy');
    });
});

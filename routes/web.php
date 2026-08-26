<?php

declare(strict_types=1);

use App\Http\Controllers\Calendar\CalendarController;
use App\Http\Controllers\Calendar\WorkScheduleController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Health\HealthEntryController;
use App\Http\Controllers\Health\HealthExportController;
use App\Http\Controllers\Health\HealthStatsController;
use App\Http\Controllers\Health\StepGoalController;
use App\Http\Controllers\People\ContactController;
use App\Http\Controllers\People\ContactDateController;
use App\Http\Controllers\People\ContactRelationshipController;
use App\Http\Controllers\Insights\InsightController;
use App\Http\Controllers\Insights\InsightPatternController;
use App\Http\Controllers\Insights\InsightRelatedController;
use App\Http\Controllers\Insights\PatternController;
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

    Route::prefix('schedules')->name('schedules.')->group(function () {
        Route::post('/', [WorkScheduleController::class, 'store'])->name('store');
        Route::patch('/{schedule}', [WorkScheduleController::class, 'update'])->name('update');
        Route::delete('/{schedule}', [WorkScheduleController::class, 'destroy'])->name('destroy');
    });
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

Route::prefix('insights')->name('insights.')->group(function () {
    Route::get('/', [InsightController::class, 'index'])->name('index');
    Route::get('/create', [InsightController::class, 'create'])->name('create');
    Route::post('/', [InsightController::class, 'store'])->name('store');
    Route::get('/{insight}', [InsightController::class, 'show'])->name('show');
    Route::get('/{insight}/edit', [InsightController::class, 'edit'])->name('edit');
    Route::patch('/{insight}', [InsightController::class, 'update'])->name('update');
    Route::delete('/{insight}', [InsightController::class, 'destroy'])->name('destroy');
    Route::patch('/{insight}/pin', [InsightController::class, 'togglePin'])->name('pin');
    Route::post('/{insight}/patterns', [InsightPatternController::class, 'store'])->name('patterns.store');
    Route::delete('/{insight}/patterns/{pattern}', [InsightPatternController::class, 'destroy'])->name('patterns.destroy');
    Route::post('/{insight}/related', [InsightRelatedController::class, 'store'])->name('related.store');
    Route::delete('/{insight}/related/{related}', [InsightRelatedController::class, 'destroy'])->name('related.destroy');
});

Route::prefix('patterns')->name('patterns.')->group(function () {
    Route::get('/', [PatternController::class, 'index'])->name('index');
    Route::post('/', [PatternController::class, 'store'])->name('store');
    Route::get('/{pattern}', [PatternController::class, 'show'])->name('show');
    Route::get('/{pattern}/edit', [PatternController::class, 'edit'])->name('edit');
    Route::patch('/{pattern}', [PatternController::class, 'update'])->name('update');
    Route::delete('/{pattern}', [PatternController::class, 'destroy'])->name('destroy');
});

Route::prefix('settings')->name('settings.')->group(function () {
    Route::prefix('relationships')->name('relationships.')->group(function () {
        Route::get('/', [RelationshipTypeController::class, 'index'])->name('index');
        Route::post('/', [RelationshipTypeController::class, 'store'])->name('store');
        Route::delete('/{relationshipType}', [RelationshipTypeController::class, 'destroy'])->name('destroy');
    });
});

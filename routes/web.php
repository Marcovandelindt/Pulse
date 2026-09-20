<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Calendar\CalendarController;
use App\Http\Controllers\Stats\StatsController;
use App\Http\Controllers\Stats\WeekReportController;
use App\Http\Controllers\Calendar\WorkScheduleController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\DayController;
use App\Http\Controllers\Health\HealthEntryController;
use App\Http\Controllers\Health\HealthExportController;
use App\Http\Controllers\Health\HealthStatsController;
use App\Http\Controllers\Health\HealthActivityController;
use App\Http\Controllers\Health\HealthHearingController;
use App\Http\Controllers\Health\HealthMobilityController;
use App\Http\Controllers\Health\HealthSleepController;
use App\Http\Controllers\Health\HealthVitalsController;
use App\Http\Controllers\Health\StepGoalController;
use App\Http\Controllers\People\ContactController;
use App\Http\Controllers\People\ContactDateController;
use App\Http\Controllers\People\ContactGiftIdeaController;
use App\Http\Controllers\People\ContactRelationshipController;
use App\Http\Controllers\Insights\InsightController;
use App\Http\Controllers\Insights\InsightPatternController;
use App\Http\Controllers\Insights\InsightRelatedController;
use App\Http\Controllers\Insights\PatternController;
use App\Http\Controllers\Changelog\ChangelogController;
use App\Http\Controllers\Ideas\IdeaController;
use App\Http\Controllers\Ideas\IdeaStatusController;
use App\Http\Controllers\Settings\RelationshipTypeController;
use App\Http\Controllers\AI\AiSettingsController;
use App\Http\Controllers\AI\ChatController;
use App\Http\Controllers\Stats\CrossStatsController;
use App\Http\Controllers\Stats\WrappedController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/day/{date?}', [DayController::class, 'show'])->name('day.show');

    Route::prefix('health')->name('health.')->group(function () {
        Route::get('/', [HealthEntryController::class,  'index'])->name('index');
        Route::post('/', [HealthEntryController::class,  'store'])->name('store');
        Route::patch('/{entry}', [HealthEntryController::class,  'update'])->name('update');
        Route::delete('/{entry}', [HealthEntryController::class,  'destroy'])->name('destroy');
        Route::get('/stats',    [HealthStatsController::class,   'index'])->name('stats');
        Route::get('/sleep',    [HealthSleepController::class,   'index'])->name('sleep');
        Route::get('/activity', [HealthActivityController::class,'index'])->name('activity');
        Route::get('/vitals',   [HealthVitalsController::class,  'index'])->name('vitals');
        Route::get('/mobility', [HealthMobilityController::class,'index'])->name('mobility');
        Route::get('/hearing',  [HealthHearingController::class, 'index'])->name('hearing');
        Route::get('/export',   [HealthExportController::class,  'index'])->name('export');
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
        Route::post('/{contact}/gift-ideas', [ContactGiftIdeaController::class, 'store'])->name('gift-ideas.store');
        Route::delete('/{contact}/gift-ideas/{giftIdea}', [ContactGiftIdeaController::class, 'destroy'])->name('gift-ideas.destroy');
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

    Route::get('/stats', [StatsController::class, 'index'])->name('stats.index');
    Route::get('/stats/week', [WeekReportController::class, 'index'])->name('stats.week');
    Route::get('/stats/wrapped', [WrappedController::class, 'index'])->name('stats.wrapped');
    Route::get('/stats/patterns', [CrossStatsController::class, 'index'])->name('stats.patterns');

    Route::prefix('ai')->name('ai.')->group(function () {
        Route::get('/chat', [ChatController::class, 'index'])->name('chat');
        Route::post('/chat', [ChatController::class, 'store'])->name('chat.store');
        Route::get('/chat/{conversation}', [ChatController::class, 'show'])->name('chat.show');
        Route::post('/chat/{conversation}/send', [ChatController::class, 'send'])->name('chat.send');
        Route::delete('/chat/{conversation}', [ChatController::class, 'destroy'])->name('chat.destroy');
        Route::get('/settings', [AiSettingsController::class, 'index'])->name('settings');
        Route::post('/settings', [AiSettingsController::class, 'update'])->name('settings.update');
    });

    Route::get('/changelog', [ChangelogController::class, 'index'])->name('changelog.index');

    Route::prefix('ideas')->name('ideas.')->group(function () {
        Route::get('/', [IdeaController::class, 'index'])->name('index');
        Route::post('/', [IdeaController::class, 'store'])->name('store');
        Route::patch('/{idea}', [IdeaController::class, 'update'])->name('update');
        Route::delete('/{idea}', [IdeaController::class, 'destroy'])->name('destroy');
        Route::patch('/{idea}/status', [IdeaStatusController::class, 'update'])->name('status.update');
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::prefix('relationships')->name('relationships.')->group(function () {
            Route::get('/', [RelationshipTypeController::class, 'index'])->name('index');
            Route::post('/', [RelationshipTypeController::class, 'store'])->name('store');
            Route::delete('/{relationshipType}', [RelationshipTypeController::class, 'destroy'])->name('destroy');
        });
    });

});

<?php

declare(strict_types=1);

use App\Http\Controllers\Finance\ExpenseCategoryController;
use App\Http\Controllers\Finance\ExpenseController;
use Illuminate\Support\Facades\Route;

Route::prefix('finance')->name('finance.')->group(function () {
    Route::get('/',                [ExpenseController::class, 'index'])->name('index');
    Route::post('/',               [ExpenseController::class, 'store'])->name('store');
    Route::get('/{expense}/edit',  [ExpenseController::class, 'edit'])->name('edit');
    Route::patch('/{expense}',     [ExpenseController::class, 'update'])->name('update');
    Route::delete('/{expense}',    [ExpenseController::class, 'destroy'])->name('destroy');

    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/',                              [ExpenseCategoryController::class, 'index'])->name('index');
        Route::post('/',                             [ExpenseCategoryController::class, 'store'])->name('store');
        Route::patch('/{expenseCategory}',           [ExpenseCategoryController::class, 'update'])->name('update');
        Route::delete('/{expenseCategory}',          [ExpenseCategoryController::class, 'destroy'])->name('destroy');
    });
});

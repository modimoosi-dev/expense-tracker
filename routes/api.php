<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ExpenseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Category routes
    Route::apiResource('categories', CategoryController::class);

    // Expense routes
    Route::apiResource('expenses', ExpenseController::class);

    // Additional expense routes
    Route::get('expenses/statistics/summary', [ExpenseController::class, 'stats'])->name('expenses.stats');
});

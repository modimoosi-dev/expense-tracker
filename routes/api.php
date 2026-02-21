<?php

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\RecurringExpenseController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.')->group(function () {
    // Category routes
    Route::apiResource('categories', CategoryController::class);

    // Expense routes
    Route::apiResource('expenses', ExpenseController::class);

    // Additional expense routes
    Route::get('expenses/statistics/summary', [ExpenseController::class, 'stats'])->name('expenses.stats');

    // Budget routes
    Route::apiResource('budgets', BudgetController::class);

    // Recurring Expense routes
    Route::apiResource('recurring-expenses', RecurringExpenseController::class);
    Route::post('recurring-expenses/{recurringExpense}/toggle', [RecurringExpenseController::class, 'toggleStatus'])->name('recurring-expenses.toggle');
    Route::post('recurring-expenses/{recurringExpense}/generate', [RecurringExpenseController::class, 'generateNow'])->name('recurring-expenses.generate');

    // Settings routes
    Route::get('settings', [SettingsController::class, 'getSettings'])->name('settings.get');
    Route::put('settings', [SettingsController::class, 'updateSettings'])->name('settings.update');
    Route::post('settings/profile-picture', [SettingsController::class, 'uploadProfilePicture'])->name('settings.profile-picture.upload');
    Route::delete('settings/profile-picture', [SettingsController::class, 'deleteProfilePicture'])->name('settings.profile-picture.delete');
    Route::get('settings/currencies', [SettingsController::class, 'getSupportedCurrencies'])->name('settings.currencies');

    // Reports routes
    Route::get('reports/monthly-trends', [ReportsController::class, 'getMonthlyTrends'])->name('reports.monthly');
    Route::get('reports/category-breakdown', [ReportsController::class, 'getCategoryBreakdown'])->name('reports.category');
    Route::get('reports/top-expenses', [ReportsController::class, 'getTopExpenses'])->name('reports.top');
    Route::get('reports/yearly-comparison', [ReportsController::class, 'getYearlyComparison'])->name('reports.yearly');
    Route::get('reports/export-csv', [ReportsController::class, 'exportToCsv'])->name('reports.export');
});

<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/categories', function () {
    return view('categories.index');
})->name('categories.index');

Route::get('/expenses', function () {
    return view('expenses.index');
})->name('expenses.index');

Route::get('/budgets', function () {
    return view('budgets.index');
})->name('budgets.index');

Route::get('/reports', function () {
    return view('reports.index');
})->name('reports.index');

Route::get('/settings', function () {
    return view('settings.index');
})->name('settings.index');

Route::get('/recurring', function () {
    return view('recurring.index');
})->name('recurring.index');

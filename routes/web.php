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

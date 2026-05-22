<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'active.user'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth', 'active.user'])
    ->name('profile');

require __DIR__.'/auth.php';

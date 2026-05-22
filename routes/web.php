<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

Volt::route('courses', 'pages.public.course-catalog')
    ->name('courses.index');

Volt::route('courses/{course:slug}', 'pages.public.course-detail')
    ->name('courses.show');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'active.user'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth', 'active.user'])
    ->name('profile');

Route::middleware(['auth', 'active.user', 'role:mentor'])
    ->prefix('mentor')
    ->name('mentor.')
    ->group(function (): void {
        Volt::route('dashboard', 'pages.mentor.dashboard')
            ->name('dashboard');

        Volt::route('courses', 'pages.mentor.course-index')
            ->name('courses.index');

        Volt::route('courses/create', 'pages.mentor.course-form')
            ->name('courses.create');

        Volt::route('courses/{course}/edit', 'pages.mentor.course-form')
            ->name('courses.edit');

        Volt::route('courses/{course}/materials', 'pages.mentor.course-material-manager')
            ->name('courses.materials');
    });

require __DIR__.'/auth.php';

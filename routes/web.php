<?php

use App\Http\Controllers\CourseMaterialViewerController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

Volt::route('courses', 'pages.public.course-catalog')
    ->name('courses.index');

Volt::route('courses/{course:slug}', 'pages.public.course-detail')
    ->name('courses.show');

Route::get('dashboard', DashboardController::class)
    ->middleware(['auth', 'active.user'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth', 'active.user'])
    ->name('profile');

Route::middleware(['auth', 'active.user', 'role:user|mentor'])->group(function (): void {
    Volt::route('my-courses', 'pages.student.my-courses')
        ->name('student.courses');

    Volt::route('wallet', 'pages.student.wallet-page')
        ->name('wallet');

    Volt::route('transactions', 'pages.student.transaction-history')
        ->name('transactions');

    Volt::route('learn/{course:slug}', 'pages.student.learning-page')
        ->name('student.learn');

    Route::get('materials/{material}/view', [CourseMaterialViewerController::class, 'show'])
        ->name('materials.view');
});

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

        Volt::route('wallet', 'pages.mentor.wallet-page')
            ->name('wallet');

        Volt::route('sales', 'pages.mentor.sales-page')
            ->name('sales');
    });

require __DIR__.'/auth.php';

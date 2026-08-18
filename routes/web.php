<?php

use App\Http\Controllers\Auth\LogoutController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::livewire('/login', 'pages::auth.login')->name('login');
    Route::livewire('/register', 'pages::auth.register')->name('register');
    Route::livewire('/forgot-password', 'pages::auth.forgot-password')->name('password.request');
    Route::livewire('/reset-password/{token}', 'pages::auth.reset-password')->name('password.reset');
});

Route::middleware('auth')->group(function () {
    Route::livewire('/', 'pages::dashboard')->name('dashboard');

    Route::prefix('trainings')->name('trainings.')->group(function () {
        Route::livewire('/', 'pages::trainings.index')->name('index');
        Route::livewire('create', 'pages::trainings.create')->name('create');
        Route::livewire('{training}', 'pages::trainings.show')->name('show');
    });

    Route::prefix('programs')->name('programs.')->group(function () {
        Route::livewire('/', 'pages::programs.index')->name('index');
        Route::livewire('create', 'pages::programs.create')->name('create');
        Route::livewire('{program}/edit', 'pages::programs.edit')->name('edit');
    });

    Route::prefix('activities')->name('activities.')->group(function () {
        Route::livewire('{activity}', 'pages::activities.show')->name('show');
    });

    Route::prefix('sequences')->name('sequences.')->group(function () {
        Route::livewire('{activity}/create', 'pages::sequences.create')->name('create');
    });

    Route::prefix('exercises')->name('exercises.')->group(function () {
        Route::livewire('/', 'pages::exercises.index')->name('index');
        Route::livewire('create', 'pages::exercises.create')->name('create');
        Route::livewire('{exercise}', 'pages::exercises.show')->name('show');
    });

    Route::prefix('metrics')->name('metrics.')->group(function () {
        Route::livewire('/', 'pages::metrics.index')->name('index');
        Route::livewire('create', 'pages::metrics.create')->name('create');
    });

    Route::prefix('account')->group(function () {
        Route::livewire('/', 'pages::account.index')->name('account');
        Route::livewire('edit', 'pages::account.edit')->name('account.edit');
    });

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::livewire('{setting}/edit', 'pages::settings.edit')->name('edit');
    });

    Route::post('/logout', LogoutController::class)->name('logout');
});

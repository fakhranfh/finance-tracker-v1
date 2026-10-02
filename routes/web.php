<?php

use App\Http\Controllers\Auth\AuthPageController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfilePageController;
use App\Http\Controllers\UserPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
})->name('landing');

Route::get('/lang/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthPageController::class, 'login'])->name('login');
    Route::get('/register', [AuthPageController::class, 'register'])->name('register');
    Route::get('/forgot-password', [AuthPageController::class, 'forgotPassword'])->name('password.request');
    Route::get('/reset-password/{token}', [AuthPageController::class, 'resetPassword'])->name('password.reset');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfilePageController::class, 'edit'])->name('profile.edit');

    Route::get('/users',[UserPageController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserPageController::class, 'create'])->name('users.create');
    Route::get('/users/{id}/edit', [UserPageController::class, 'edit'])->name('users.edit');
});

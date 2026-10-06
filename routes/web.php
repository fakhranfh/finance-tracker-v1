<?php

use App\Http\Controllers\AccountPageController;
use App\Http\Controllers\Auth\AuthPageController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfilePageController;
use App\Http\Controllers\RecordPageController;
use App\Http\Middleware\EnsureNoUsersExist;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(User::exists() ? 'login' : 'register'))->name('home');

Route::get('/lang/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthPageController::class, 'login'])->name('login');
    Route::get('/register', [AuthPageController::class, 'register'])->middleware(EnsureNoUsersExist::class)->name('register');
    Route::get('/forgot-password', [AuthPageController::class, 'forgotPassword'])->name('password.request');
    Route::get('/reset-password/{token}', [AuthPageController::class, 'resetPassword'])->name('password.reset');

    if (app()->isLocal()) {
        Route::get('/reset-password-preview', fn () => redirect()->route('password.reset', [
            'token' => 'preview-token',
            'email' => 'dev@example.com',
        ]))->name('password.reset.preview');

        Route::get('/reset-password-email-preview', function () {
            $user = User::first() ?? new User(['name' => 'Preview', 'email' => 'dev@example.com']);

            return (new ResetPassword('preview-token'))->toMail($user);
        })->name('password.reset.email-preview');
    }
});

Route::middleware('auth')->group(function (): void {
    Route::get('/email/verify', [AuthPageController::class, 'verifyEmailNotice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('dashboard');
    })->middleware('signed')->name('verification.verify');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfilePageController::class, 'edit'])->name('profile.edit');

    Route::get('/accounts', [AccountPageController::class, 'index'])->name('accounts.index');
    Route::get('/accounts/create', [AccountPageController::class, 'create'])->name('accounts.create');
    Route::get('/accounts/{id}/edit', [AccountPageController::class, 'edit'])->name('accounts.edit');
    Route::get('/accounts/{id}/summary', [AccountPageController::class, 'summary'])->name('accounts.summary');

    Route::get('/records', [RecordPageController::class, 'index'])->name('records.index');
    Route::get('/records/create', [RecordPageController::class, 'create'])->name('records.create');
    Route::get('/records/{id}/edit', [RecordPageController::class, 'edit'])->name('records.edit');
});

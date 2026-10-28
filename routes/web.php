<?php

use App\Http\Controllers\AccountPageController;
use App\Http\Controllers\Auth\AuthPageController;
use App\Http\Controllers\CategoryPageController;
use App\Http\Controllers\DebtPageController;
use App\Http\Controllers\DebtPaymentPageController;
use App\Http\Controllers\GoalPageController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfilePageController;
use App\Http\Controllers\RecordPageController;
use App\Http\Controllers\SubDebtPageController;
use App\Http\Controllers\SubGoalPageController;
use App\Http\Controllers\WebhookPageController;
use App\Http\Middleware\EnsureNoUsersExist;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(User::exists() ? 'login' : 'register'))->name('home');

Route::get('/manifest.webmanifest', fn () => response()->json([
    'name' => config('app.name'),
    'short_name' => config('app.name'),
    'start_url' => '/dashboard',
    'scope' => '/',
    'display' => 'standalone',
    'background_color' => '#ffffff',
    'theme_color' => '#004ac6',
    'icons' => [
        ['src' => '/icons/icon-128.png', 'sizes' => '128x128', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => '/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
    ],
], 200, ['Content-Type' => 'application/manifest+json']))->name('manifest');

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

    Route::prefix('accounts')->group(function (): void {
        Route::get('/', [AccountPageController::class, 'index'])->name('accounts.index');
        Route::get('/create', [AccountPageController::class, 'create'])->name('accounts.create');
        Route::get('/{id}/edit', [AccountPageController::class, 'edit'])->name('accounts.edit');
        Route::get('/{id}/summary', [AccountPageController::class, 'summary'])->name('accounts.summary');
    });

    Route::prefix('records')->group(function (): void {
        Route::get('/', [RecordPageController::class, 'index'])->name('records.index');
        Route::get('/import', [RecordPageController::class, 'import'])->name('records.import');
        Route::get('/create', [RecordPageController::class, 'create'])->name('records.create');
        Route::get('/{id}/edit', [RecordPageController::class, 'edit'])->name('records.edit');
    });

    Route::prefix('categories')->group(function (): void {
        Route::get('/', [CategoryPageController::class, 'index'])->name('categories.index');
        Route::get('/create', [CategoryPageController::class, 'create'])->name('categories.create');
        Route::get('/{id}/edit', [CategoryPageController::class, 'edit'])->name('categories.edit');
    });

    Route::prefix('goals')->group(function (): void {
        Route::get('/', [GoalPageController::class, 'index'])->name('goals.index');
        Route::get('/create', [GoalPageController::class, 'create'])->name('goals.create');
        Route::get('/{id}', [GoalPageController::class, 'show'])->whereNumber('id')->name('goals.show');
        Route::get('/{id}/edit', [GoalPageController::class, 'edit'])->name('goals.edit');

        Route::prefix('{goalId}/sub-goals')->whereNumber('goalId')->group(function (): void {
            Route::get('/', [SubGoalPageController::class, 'index'])->name('goals.sub-goals.index');
            Route::get('/edit', [SubGoalPageController::class, 'edit'])->name('goals.sub-goals.edit');
            Route::get('/{id}/edit', [SubGoalPageController::class, 'editItem'])->whereNumber('id')->name('goals.sub-goals.edit-item');
            Route::get('/{id}', [SubGoalPageController::class, 'show'])->whereNumber('id')->name('goals.sub-goals.show');
        });
    });

    Route::prefix('webhooks')->group(function (): void {
        Route::get('/', [WebhookPageController::class, 'index'])->name('webhooks.index');
        Route::get('/create', [WebhookPageController::class, 'create'])->name('webhooks.create');
        Route::get('/api-docs', [WebhookPageController::class, 'apiDocs'])->name('webhooks.api-docs');
        Route::get('/{id}', [WebhookPageController::class, 'show'])->whereNumber('id')->name('webhooks.show');
        Route::get('/{id}/edit', [WebhookPageController::class, 'edit'])->name('webhooks.edit');
        Route::get('/{id}/test', [WebhookPageController::class, 'test'])->whereNumber('id')->name('webhooks.test');
        Route::get('/{id}/logs', [WebhookPageController::class, 'logs'])->whereNumber('id')->name('webhooks.logs');
    });

    foreach (['debts' => 'debt', 'receivables' => 'receivable'] as $path => $type) {
        Route::prefix($path)->group(function () use ($path, $type): void {
            Route::get('/', [DebtPageController::class, 'index'])->defaults('type', $type)->name("{$path}.index");
            Route::get('/create', [DebtPageController::class, 'create'])->defaults('type', $type)->name("{$path}.create");
            Route::get('/{id}/edit', [DebtPageController::class, 'edit'])->defaults('type', $type)->name("{$path}.edit");

            Route::prefix('{debtId}')->whereNumber('debtId')->group(function () use ($path, $type): void {
                Route::prefix('payments')->group(function () use ($path, $type): void {
                    Route::get('/', [DebtPaymentPageController::class, 'index'])->defaults('type', $type)->name("{$path}.payments.index");
                    Route::get('/create', [DebtPaymentPageController::class, 'create'])->defaults('type', $type)->name("{$path}.payments.create");
                    Route::get('/existing', [DebtPaymentPageController::class, 'existing'])->defaults('type', $type)->name("{$path}.payments.existing");
                    Route::get('/{id}/edit', [DebtPaymentPageController::class, 'edit'])->where('id', '-?[0-9]+')->defaults('type', $type)->name("{$path}.payments.edit");
                });

                Route::prefix('sub-debts')->group(function () use ($path, $type): void {
                    Route::get('/edit', [SubDebtPageController::class, 'edit'])->defaults('type', $type)->name("{$path}.sub-debts.edit");
                    Route::get('/create', [SubDebtPageController::class, 'create'])->defaults('type', $type)->name("{$path}.sub-debts.create");
                    Route::get('/{id}/edit', [SubDebtPageController::class, 'editItem'])->whereNumber('id')->defaults('type', $type)->name("{$path}.sub-debts.edit-item");
                    Route::get('/', [SubDebtPageController::class, 'index'])->defaults('type', $type)->name("{$path}.sub-debts.index");
                });
            });
        });
    }
});

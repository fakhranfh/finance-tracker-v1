<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AccountRecordDeleteController;
use App\Http\Controllers\Api\AccountRecordGenerateController;
use App\Http\Controllers\Api\AccountSummaryController;
use App\Http\Controllers\Api\Auth\DevAutoLoginController;
use App\Http\Controllers\Api\Auth\ForgotPasswordController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\ResetPasswordController;
use App\Http\Controllers\Api\Auth\VerificationNotificationController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\GoalController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RecordController;
use App\Http\Middleware\EnsureLocalEnvironment;
use App\Http\Middleware\EnsureNoUsersExist;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are session-based (they run through the "web" middleware
| group so the authenticated Blade views can consume them via fetch with
| the CSRF token), keeping all backend logic out of server-rendered
| controllers.
|
*/

Route::middleware('web')->name('api.')->group(function (): void {
    Route::post('/register', RegisterController::class)->middleware(EnsureNoUsersExist::class)->name('register');
    Route::post('/login', LoginController::class)->name('login');
    Route::post('/dev/auto-login', DevAutoLoginController::class)
        ->middleware(EnsureLocalEnvironment::class)
        ->name('dev.auto-login');
    Route::post('/forgot-password', ForgotPasswordController::class)->name('forgot-password');
    Route::post('/reset-password', ResetPasswordController::class)->name('reset-password');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/email/verification-notification', VerificationNotificationController::class)
            ->name('verification.send');
        Route::put('/accounts/order', [AccountController::class, 'reorder'])->name('accounts.reorder');
        Route::delete('/accounts', [AccountController::class, 'bulkDestroy'])->name('accounts.bulk-destroy');
        Route::get('/accounts/{account}/summary', [AccountSummaryController::class, 'show'])->name('accounts.summary');
        Route::post('/accounts/{account}/records/generate', AccountRecordGenerateController::class)
            ->middleware(EnsureLocalEnvironment::class)
            ->name('accounts.records.generate');
        Route::delete('/accounts/{account}/records', AccountRecordDeleteController::class)
            ->middleware(EnsureLocalEnvironment::class)
            ->name('accounts.records.destroy');
        Route::post('/accounts/generate', [AccountController::class, 'generate'])
            ->middleware(EnsureLocalEnvironment::class)
            ->name('accounts.generate');
        Route::resource('accounts', AccountController::class)
            ->parameters(['accounts' => 'account'])
            ->except('create', 'edit')
            ->names('accounts');
        Route::get('/records/export/{format}', [RecordController::class, 'export'])
            ->whereIn('format', ['xlsx', 'pdf'])
            ->name('records.export');
        Route::get('/records/summary', [RecordController::class, 'summary'])->name('records.summary');
        Route::delete('/records', [RecordController::class, 'bulkDestroy'])->name('records.bulk-destroy');
        Route::post('/records/generate', [RecordController::class, 'generate'])
            ->middleware(EnsureLocalEnvironment::class)
            ->name('records.generate');
        Route::resource('records', RecordController::class)
            ->parameters(['records' => 'record'])
            ->except('create', 'edit')
            ->names('records');
        Route::delete('/categories', [CategoryController::class, 'bulkDestroy'])->name('categories.bulk-destroy');
        Route::post('/categories/generate', [CategoryController::class, 'generate'])
            ->middleware(EnsureLocalEnvironment::class)
            ->name('categories.generate');
        Route::resource('categories', CategoryController::class)
            ->parameters(['categories' => 'category'])
            ->except('create', 'edit')
            ->names('categories');
        Route::delete('/goals', [GoalController::class, 'bulkDestroy'])->name('goals.bulk-destroy');
        Route::post('/goals/generate', [GoalController::class, 'generate'])
            ->middleware(EnsureLocalEnvironment::class)
            ->name('goals.generate');
        Route::resource('goals', GoalController::class)
            ->parameters(['goals' => 'goal'])
            ->except('create', 'edit')
            ->names('goals');
        Route::post('/logout', LogoutController::class)->name('logout');
    });

    Route::middleware(['auth:sanctum', 'verified'])->group(function (): void {
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });
});

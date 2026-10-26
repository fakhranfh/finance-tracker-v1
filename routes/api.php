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
use App\Http\Controllers\Api\DebtController;
use App\Http\Controllers\Api\GoalController;
use App\Http\Controllers\Api\GoalSavingController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RecordController;
use App\Http\Controllers\Api\RecordImportController;
use App\Http\Controllers\Api\SubDebtController;
use App\Http\Controllers\Api\SubGoalController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\WebhookLogController;
use App\Http\Controllers\Api\WebhookRecordController;
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
        Route::get('/records/imports', [RecordImportController::class, 'index'])->name('records.imports.index');
        Route::post('/records/imports', [RecordImportController::class, 'store'])->name('records.imports.store');
        Route::get('/records/imports/{import}', [RecordImportController::class, 'show'])->whereNumber('import')->name('records.imports.show');
        Route::put('/records/imports/{import}', [RecordImportController::class, 'update'])->whereNumber('import')->name('records.imports.update');
        Route::post('/records/imports/{import}/values', [RecordImportController::class, 'values'])->whereNumber('import')->name('records.imports.values');
        Route::post('/records/imports/{import}/run', [RecordImportController::class, 'run'])->whereNumber('import')->name('records.imports.run');
        Route::delete('/records/imports/{import}', [RecordImportController::class, 'destroy'])->whereNumber('import')->name('records.imports.destroy');
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
        Route::get('/goals/{goal}/savings', [GoalSavingController::class, 'index'])->name('goals.savings.index');
        Route::delete('/goals/{goal}/savings', [GoalSavingController::class, 'bulkDestroy'])->name('goals.savings.bulk-destroy');
        Route::post('/goals/{goal}/savings/generate', [GoalSavingController::class, 'generate'])
            ->middleware(EnsureLocalEnvironment::class)
            ->name('goals.savings.generate');
        Route::post('/goals/{goal}/savings', [GoalSavingController::class, 'store'])->name('goals.savings.store');
        Route::put('/goals/{goal}/savings/{saving}', [GoalSavingController::class, 'update'])
            ->scopeBindings()
            ->name('goals.savings.update');
        Route::delete('/goals/{goal}/savings/{saving}', [GoalSavingController::class, 'destroy'])
            ->scopeBindings()
            ->name('goals.savings.destroy');
        Route::resource('goals', GoalController::class)
            ->parameters(['goals' => 'goal'])
            ->except('create', 'edit')
            ->names('goals');
        Route::get('/goals/{goal}/sub-goals', [SubGoalController::class, 'index'])->name('goals.sub-goals.index');
        Route::put('/goals/{goal}/sub-goals', [SubGoalController::class, 'sync'])->name('goals.sub-goals.sync');
        Route::get('/goals/{goal}/sub-goals/{subGoal}', [SubGoalController::class, 'show'])
            ->scopeBindings()
            ->name('goals.sub-goals.show');
        Route::put('/goals/{goal}/sub-goals/{subGoal}', [SubGoalController::class, 'update'])
            ->scopeBindings()
            ->name('goals.sub-goals.update');
        Route::delete('/webhooks', [WebhookController::class, 'bulkDestroy'])->name('webhooks.bulk-destroy');
        Route::post('/webhooks/generate', [WebhookController::class, 'generate'])
            ->middleware(EnsureLocalEnvironment::class)
            ->name('webhooks.generate');
        Route::get('/webhooks/{webhook}/logs', [WebhookLogController::class, 'index'])->name('webhooks.logs.index');
        Route::delete('/webhooks/{webhook}/logs', [WebhookLogController::class, 'destroy'])->name('webhooks.logs.destroy');
        Route::post('/webhooks/{webhook}/test', [WebhookController::class, 'test'])->name('webhooks.test');
        Route::resource('webhooks', WebhookController::class)
            ->parameters(['webhooks' => 'webhook'])
            ->except('create', 'edit')
            ->names('webhooks');
        Route::delete('/debts', [DebtController::class, 'bulkDestroy'])->name('debts.bulk-destroy');
        Route::post('/debts/generate', [DebtController::class, 'generate'])
            ->middleware(EnsureLocalEnvironment::class)
            ->name('debts.generate');
        Route::get('/debts/{debt}/sub-debts', [SubDebtController::class, 'index'])->name('debts.sub-debts.index');
        Route::post('/debts/{debt}/sub-debts', [SubDebtController::class, 'store'])->name('debts.sub-debts.store');
        Route::delete('/debts/{debt}/sub-debts', [SubDebtController::class, 'bulkDestroy'])->name('debts.sub-debts.bulk-destroy');
        Route::post('/debts/{debt}/sub-debts/generate', [SubDebtController::class, 'generate'])
            ->middleware(EnsureLocalEnvironment::class)
            ->name('debts.sub-debts.generate');
        Route::delete('/debts/{debt}/sub-debts/{subDebt}', [SubDebtController::class, 'destroy'])
            ->scopeBindings()
            ->name('debts.sub-debts.destroy');
        Route::put('/debts/{debt}/sub-debts', [SubDebtController::class, 'sync'])->name('debts.sub-debts.sync');
        Route::get('/debts/{debt}/sub-debts/{subDebt}', [SubDebtController::class, 'show'])
            ->scopeBindings()
            ->name('debts.sub-debts.show');
        Route::put('/debts/{debt}/sub-debts/{subDebt}', [SubDebtController::class, 'update'])
            ->scopeBindings()
            ->name('debts.sub-debts.update');
        Route::resource('debts', DebtController::class)
            ->parameters(['debts' => 'debt'])
            ->except('create', 'edit')
            ->names('debts');
        Route::post('/logout', LogoutController::class)->name('logout');
    });

    Route::middleware(['auth:sanctum', 'verified'])->group(function (): void {
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });
});

Route::post('/webhooks/records', WebhookRecordController::class)
    ->middleware('throttle:60,1')
    ->name('api.webhooks.records.store');

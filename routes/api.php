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
use App\Http\Controllers\Api\DebtPaymentController;
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

        Route::prefix('accounts')->group(function (): void {
            Route::put('/order', [AccountController::class, 'reorder'])->name('accounts.reorder');
            Route::delete('/', [AccountController::class, 'bulkDestroy'])->name('accounts.bulk-destroy');
            Route::get('/{account}/summary', [AccountSummaryController::class, 'show'])->name('accounts.summary');
            Route::post('/{account}/records/generate', AccountRecordGenerateController::class)
                ->middleware(EnsureLocalEnvironment::class)
                ->name('accounts.records.generate');
            Route::delete('/{account}/records', AccountRecordDeleteController::class)
                ->middleware(EnsureLocalEnvironment::class)
                ->name('accounts.records.destroy');
            Route::post('/generate', [AccountController::class, 'generate'])
                ->middleware(EnsureLocalEnvironment::class)
                ->name('accounts.generate');
        });
        Route::resource('accounts', AccountController::class)
            ->parameters(['accounts' => 'account'])
            ->except('create', 'edit')
            ->names('accounts');

        Route::prefix('records')->group(function (): void {
            Route::get('/export/{format}', [RecordController::class, 'export'])
                ->whereIn('format', ['xlsx', 'pdf'])
                ->name('records.export');

            Route::prefix('imports')->group(function (): void {
                Route::get('/', [RecordImportController::class, 'index'])->name('records.imports.index');
                Route::post('/', [RecordImportController::class, 'store'])->name('records.imports.store');
                Route::prefix('{import}')->whereNumber('import')->group(function (): void {
                    Route::get('/', [RecordImportController::class, 'show'])->name('records.imports.show');
                    Route::put('/', [RecordImportController::class, 'update'])->name('records.imports.update');
                    Route::post('/values', [RecordImportController::class, 'values'])->name('records.imports.values');
                    Route::post('/run', [RecordImportController::class, 'run'])->name('records.imports.run');
                    Route::delete('/', [RecordImportController::class, 'destroy'])->name('records.imports.destroy');
                });
            });

            Route::get('/summary', [RecordController::class, 'summary'])->name('records.summary');
            Route::delete('/', [RecordController::class, 'bulkDestroy'])->name('records.bulk-destroy');
            Route::post('/generate', [RecordController::class, 'generate'])
                ->middleware(EnsureLocalEnvironment::class)
                ->name('records.generate');
        });
        Route::resource('records', RecordController::class)
            ->parameters(['records' => 'record'])
            ->except('create', 'edit')
            ->names('records');

        Route::prefix('categories')->group(function (): void {
            Route::delete('/', [CategoryController::class, 'bulkDestroy'])->name('categories.bulk-destroy');
            Route::post('/generate', [CategoryController::class, 'generate'])
                ->middleware(EnsureLocalEnvironment::class)
                ->name('categories.generate');
        });
        Route::resource('categories', CategoryController::class)
            ->parameters(['categories' => 'category'])
            ->except('create', 'edit')
            ->names('categories');

        Route::prefix('goals')->group(function (): void {
            Route::delete('/', [GoalController::class, 'bulkDestroy'])->name('goals.bulk-destroy');
            Route::post('/generate', [GoalController::class, 'generate'])
                ->middleware(EnsureLocalEnvironment::class)
                ->name('goals.generate');

            Route::prefix('{goal}/savings')->group(function (): void {
                Route::get('/', [GoalSavingController::class, 'index'])->name('goals.savings.index');
                Route::delete('/', [GoalSavingController::class, 'bulkDestroy'])->name('goals.savings.bulk-destroy');
                Route::post('/generate', [GoalSavingController::class, 'generate'])
                    ->middleware(EnsureLocalEnvironment::class)
                    ->name('goals.savings.generate');
                Route::post('/', [GoalSavingController::class, 'store'])->name('goals.savings.store');
                Route::put('/{saving}', [GoalSavingController::class, 'update'])
                    ->scopeBindings()
                    ->name('goals.savings.update');
                Route::delete('/{saving}', [GoalSavingController::class, 'destroy'])
                    ->scopeBindings()
                    ->name('goals.savings.destroy');
            });
        });
        Route::resource('goals', GoalController::class)
            ->parameters(['goals' => 'goal'])
            ->except('create', 'edit')
            ->names('goals');
        Route::prefix('goals/{goal}/sub-goals')->group(function (): void {
            Route::get('/', [SubGoalController::class, 'index'])->name('goals.sub-goals.index');
            Route::put('/', [SubGoalController::class, 'sync'])->name('goals.sub-goals.sync');
            Route::get('/{subGoal}', [SubGoalController::class, 'show'])
                ->scopeBindings()
                ->name('goals.sub-goals.show');
            Route::put('/{subGoal}', [SubGoalController::class, 'update'])
                ->scopeBindings()
                ->name('goals.sub-goals.update');
        });

        Route::prefix('webhooks')->group(function (): void {
            Route::delete('/', [WebhookController::class, 'bulkDestroy'])->name('webhooks.bulk-destroy');
            Route::post('/generate', [WebhookController::class, 'generate'])
                ->middleware(EnsureLocalEnvironment::class)
                ->name('webhooks.generate');
            Route::get('/{webhook}/logs', [WebhookLogController::class, 'index'])->name('webhooks.logs.index');
            Route::delete('/{webhook}/logs', [WebhookLogController::class, 'destroy'])->name('webhooks.logs.destroy');
            Route::post('/{webhook}/test', [WebhookController::class, 'test'])->name('webhooks.test');
            Route::post('/{webhook}/rotate-token', [WebhookController::class, 'rotateToken'])->name('webhooks.rotate-token');
        });
        Route::resource('webhooks', WebhookController::class)
            ->parameters(['webhooks' => 'webhook'])
            ->except('create', 'edit')
            ->names('webhooks');

        Route::prefix('debts')->group(function (): void {
            Route::delete('/', [DebtController::class, 'bulkDestroy'])->name('debts.bulk-destroy');
            Route::post('/generate', [DebtController::class, 'generate'])
                ->middleware(EnsureLocalEnvironment::class)
                ->name('debts.generate');

            Route::prefix('{debt}')->group(function (): void {
                Route::post('/forgive', [DebtController::class, 'forgive'])->name('debts.forgive');

                Route::prefix('payments')->group(function (): void {
                    Route::get('/', [DebtPaymentController::class, 'index'])->name('debts.payments.index');
                    Route::get('/available', [DebtPaymentController::class, 'available'])->name('debts.payments.available');
                    Route::post('/', [DebtPaymentController::class, 'store'])->name('debts.payments.store');
                    Route::delete('/{payment}', [DebtPaymentController::class, 'destroy'])->where('payment', '-?[0-9]+')->name('debts.payments.destroy');
                });

                Route::prefix('sub-debts')->group(function (): void {
                    Route::get('/', [SubDebtController::class, 'index'])->name('debts.sub-debts.index');
                    Route::post('/', [SubDebtController::class, 'store'])->name('debts.sub-debts.store');
                    Route::delete('/', [SubDebtController::class, 'bulkDestroy'])->name('debts.sub-debts.bulk-destroy');
                    Route::post('/generate', [SubDebtController::class, 'generate'])
                        ->middleware(EnsureLocalEnvironment::class)
                        ->name('debts.sub-debts.generate');
                    Route::delete('/{subDebt}', [SubDebtController::class, 'destroy'])
                        ->scopeBindings()
                        ->name('debts.sub-debts.destroy');
                    Route::post('/{subDebt}/forgive', [SubDebtController::class, 'forgive'])
                        ->scopeBindings()
                        ->name('debts.sub-debts.forgive');
                    Route::put('/', [SubDebtController::class, 'sync'])->name('debts.sub-debts.sync');
                    Route::get('/{subDebt}', [SubDebtController::class, 'show'])
                        ->scopeBindings()
                        ->name('debts.sub-debts.show');
                    Route::put('/{subDebt}', [SubDebtController::class, 'update'])
                        ->scopeBindings()
                        ->name('debts.sub-debts.update');
                });
            });
        });
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

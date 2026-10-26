<?php

namespace App\Providers;

use App\Repositories\Account\AccountRepository;
use App\Repositories\Account\AccountRepositoryInterface;
use App\Repositories\AccountBalance\AccountBalanceRepository;
use App\Repositories\AccountBalance\AccountBalanceRepositoryInterface;
use App\Repositories\AccountSummary\AccountSummaryRepository;
use App\Repositories\AccountSummary\AccountSummaryRepositoryInterface;
use App\Repositories\Category\CategoryRepository;
use App\Repositories\Category\CategoryRepositoryInterface;
use App\Repositories\Debt\DebtRepository;
use App\Repositories\Debt\DebtRepositoryInterface;
use App\Repositories\Goal\GoalRepository;
use App\Repositories\Goal\GoalRepositoryInterface;
use App\Repositories\GoalSaving\GoalSavingRepository;
use App\Repositories\GoalSaving\GoalSavingRepositoryInterface;
use App\Repositories\Receivable\ReceivableRepository;
use App\Repositories\Receivable\ReceivableRepositoryInterface;
use App\Repositories\Record\RecordRepository;
use App\Repositories\Record\RecordRepositoryInterface;
use App\Repositories\RecordImport\RecordImportRepository;
use App\Repositories\RecordImport\RecordImportRepositoryInterface;
use App\Repositories\SubGoal\SubGoalRepository;
use App\Repositories\SubGoal\SubGoalRepositoryInterface;
use App\Repositories\User\UserRepository;
use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\Webhook\WebhookRepository;
use App\Repositories\Webhook\WebhookRepositoryInterface;
use App\Repositories\WebhookLog\WebhookLogRepository;
use App\Repositories\WebhookLog\WebhookLogRepositoryInterface;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ReceivableRepositoryInterface::class, ReceivableRepository::class);
        $this->app->bind(DebtRepositoryInterface::class, DebtRepository::class);
        $this->app->bind(WebhookRepositoryInterface::class, WebhookRepository::class);
        $this->app->bind(WebhookLogRepositoryInterface::class, WebhookLogRepository::class);
        $this->app->bind(SubGoalRepositoryInterface::class, SubGoalRepository::class);
        $this->app->bind(GoalRepositoryInterface::class, GoalRepository::class);
        $this->app->bind(GoalSavingRepositoryInterface::class, GoalSavingRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(RecordRepositoryInterface::class, RecordRepository::class);
        $this->app->bind(RecordImportRepositoryInterface::class, RecordImportRepository::class);
        $this->app->bind(AccountRepositoryInterface::class, AccountRepository::class);
        $this->app->bind(AccountBalanceRepositoryInterface::class, AccountBalanceRepository::class);
        $this->app->bind(AccountSummaryRepositoryInterface::class, AccountSummaryRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject(__('auth.mail.reset_subject'))
                ->greeting(__('auth.mail.reset_greeting', ['name' => $notifiable->name]))
                ->line(__('auth.mail.reset_intro'))
                ->action(__('auth.mail.reset_action'), $url)
                ->line(__('auth.mail.reset_expire', ['count' => config('auth.passwords.users.expire')]))
                ->line(__('auth.mail.reset_outro'))
                ->salutation(new HtmlString(e(__('auth.mail.salutation')).'<br>'.e(config('app.name'))));
        });

        VerifyEmail::toMailUsing(function (object $notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject(__('auth.mail.verify_subject'))
                ->greeting(__('auth.mail.reset_greeting', ['name' => $notifiable->name]))
                ->line(__('auth.mail.verify_intro'))
                ->action(__('auth.mail.verify_action'), $url)
                ->line(__('auth.mail.verify_outro'))
                ->salutation(new HtmlString(e(__('auth.mail.salutation')).'<br>'.e(config('app.name'))));
        });

        Password::defaults(fn () => Password::min(8)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols());
    }
}

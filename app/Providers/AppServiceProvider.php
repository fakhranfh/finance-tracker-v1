<?php

namespace App\Providers;

use App\Repositories\Account\AccountRepository;
use App\Repositories\Account\AccountRepositoryInterface;
use App\Repositories\Record\RecordRepository;
use App\Repositories\Record\RecordRepositoryInterface;
use App\Repositories\User\UserRepository;
use App\Repositories\User\UserRepositoryInterface;
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
        $this->app->bind(RecordRepositoryInterface::class, RecordRepository::class);
        $this->app->bind(AccountRepositoryInterface::class, AccountRepository::class);
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

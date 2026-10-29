<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TerminalPasswordResetNotification extends Notification
{
    public function __construct(
        protected string $url,
        protected int $minutes,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('terminal.mail.subject'))
            ->line(__('terminal.mail.intro'))
            ->action(__('terminal.mail.action'), $this->url)
            ->line(__('terminal.mail.expires', ['minutes' => $this->minutes]))
            ->line(__('terminal.mail.ignore'));
    }
}

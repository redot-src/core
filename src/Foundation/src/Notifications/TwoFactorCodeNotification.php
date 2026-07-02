<?php

namespace Redot\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCodeNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $code,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $expireMinutes = config('auth.two_factor.expire', 10);

        return (new MailMessage)
            ->subject(__('Your Verification Code for :app', ['app' => config('app.name')]))
            ->line(__('Use the following code to verify your identity: **:code**', ['code' => $this->code]))
            ->line(__('This code expires in :minutes minutes.', ['minutes' => $expireMinutes]))
            ->line(__('If you did not request this, you can safely ignore this email.'));
    }
}

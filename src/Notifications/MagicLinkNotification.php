<?php

namespace Redot\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Redot\Models\LoginToken;

class MagicLinkNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public LoginToken $loginToken,
        public string $verifyRoute,
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
        $expireMinutes = config('auth.magic_link.expire', 15);

        return (new MailMessage)
            ->subject(__('Your Login Code for :app', ['app' => config('app.name')]))
            ->line(__('Click the button below to log in, or use the code: **:code**', ['code' => $this->loginToken->code]))
            ->action(__('Login Now'), route($this->verifyRoute, ['token' => $this->loginToken->token]))
            ->line(__('This link expires in :minutes minutes.', ['minutes' => $expireMinutes]))
            ->line(__('If you did not request this, you can safely ignore this email.'));
    }
}

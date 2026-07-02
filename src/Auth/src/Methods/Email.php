<?php

namespace Redot\Auth\Methods;

use Illuminate\Contracts\Auth\Authenticatable;
use Redot\Notifications\TwoFactorCodeNotification;

/**
 * One-time code method that delivers codes by email notification.
 */
class Email extends OneTimeCode
{
    /**
     * The notification class used to deliver codes.
     *
     * @var class-string
     */
    protected static string $notificationClass = TwoFactorCodeNotification::class;

    /**
     * Swap the notification used to deliver codes.
     */
    public static function useNotificationClass(string $class): void
    {
        static::$notificationClass = $class;
    }

    /**
     * The method is keyed as "email".
     */
    public function key(): string
    {
        return 'email';
    }

    /**
     * Email confirmation is tracked on two_factor_email_confirmed_at.
     */
    protected function column(): string
    {
        return 'two_factor_email_confirmed_at';
    }

    /**
     * Deliver the plain code to the user by email.
     */
    protected function deliver(Authenticatable $user, string $code): void
    {
        $notificationClass = static::$notificationClass;

        $user->notify(new $notificationClass($code));
    }
}

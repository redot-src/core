<?php

namespace Redot\Auth\Methods;

use Illuminate\Contracts\Auth\Authenticatable;
use Redot\Notifications\TwoFactorCodeNotification;

class Email extends OneTimeCode
{
    protected static string $notificationClass = TwoFactorCodeNotification::class;

    public static function useNotificationClass(string $class): void
    {
        static::$notificationClass = $class;
    }

    public function key(): string
    {
        return 'email';
    }

    protected function column(): string
    {
        return 'two_factor_email_confirmed_at';
    }

    protected function deliver(Authenticatable $user, string $code): void
    {
        $notificationClass = static::$notificationClass;

        $user->notify(new $notificationClass($code));
    }
}

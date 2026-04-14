<?php

namespace Redot\Auth;

use Closure;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Redot\Auth\Actions\Login;
use Redot\Auth\Routes\EmailVerificationRoutes;
use Redot\Auth\Routes\LockRoutes;
use Redot\Auth\Routes\LoginRoutes;
use Redot\Auth\Routes\LogoutRoutes;
use Redot\Auth\Routes\MagicLinkRoutes;
use Redot\Auth\Routes\PasswordResetRoutes;
use Redot\Auth\Routes\RegistrationRoutes;

class RedotAuthManager
{
    protected const REGISTRARS = [
        'login' => LoginRoutes::class,
        'register' => RegistrationRoutes::class,
        'password-reset' => PasswordResetRoutes::class,
        'magic-link' => MagicLinkRoutes::class,
        'email-verification' => EmailVerificationRoutes::class,
        'logout' => LogoutRoutes::class,
        'lock-screen' => LockRoutes::class,
    ];

    public function routes(
        string $guard,
        ?Closure $scope = null,
        array $views = [],
        array $disable = [],
        array $registrars = [],
        ?string $home = null,
    ): void {
        $context = $this->resolveContext($guard, $scope, $views, $disable, $home);

        foreach (static::REGISTRARS as $feature => $default) {
            if (! $context->featureEnabled($feature)) {
                continue;
            }

            $class = $registrars[$feature] ?? $default;
            app($class)->register($context);
        }
    }

    protected function resolveContext(string $guard, ?Closure $scope, array $views, array $disable, ?string $home): AuthContext
    {
        $guardConfig = config('auth.guards.' . $guard);

        if (! is_array($guardConfig)) {
            throw new InvalidArgumentException("Guard [$guard] is not configured.");
        }

        $provider = $guardConfig['provider'] ?? null;

        if (! is_string($provider) || $provider === '') {
            throw new InvalidArgumentException("Guard [$guard] has no provider.");
        }

        $model = config('auth.providers.' . $provider . '.model');
        $driver = $guardConfig['driver'] ?? null;
        $api = in_array($driver, ['sanctum', 'passport', 'jwt', 'api']);

        if (! is_string($model) || ! class_exists($model)) {
            throw new InvalidArgumentException("Provider [$provider] model is invalid.");
        }

        $namePrefix = $this->currentNamePrefix();

        return new AuthContext(
            guard: $guard,
            provider: $provider,
            broker: $this->resolveBroker($provider),
            model: $model,
            scope: $scope,
            api: $api,
            namePrefix: $namePrefix,
            views: $views,
            home: $home ?? $namePrefix . 'index',
            identifiers: Login::getIdentifiers($provider),
            disable: $disable,
        );
    }

    protected function resolveBroker(string $provider): string
    {
        $passwords = config('auth.passwords', []);

        foreach ($passwords as $broker => $config) {
            if (($config['provider'] ?? null) === $provider) {
                return $broker;
            }
        }

        return $provider;
    }

    protected function currentNamePrefix(): string
    {
        $router = Route::getFacadeRoot();
        $stack = $router->getGroupStack();

        if ($stack === []) {
            return '';
        }

        return (string) ($stack[array_key_last($stack)]['as'] ?? '');
    }
}

<?php

namespace Redot\Auth;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Redot\Auth\Middleware\Locked;

class AuthContext
{
    public readonly array $disabled;

    public function __construct(
        public readonly string $guard,
        public readonly string $provider,
        public readonly string $broker,
        public readonly string $model,
        public readonly ?\Closure $scope,
        public readonly bool $api,
        public readonly string $namePrefix,
        public readonly array $views,
        public readonly string $home,
        public readonly array $identifiers = ['email'],
        array $disable = [],
    ) {
        $this->disabled = $this->resolveDisabled($disable);
    }

    public function routeName(string $name): string
    {
        return $this->namePrefix . $name;
    }

    public function featureEnabled(string $feature): bool
    {
        return ! ($this->disabled[$feature] ?? false);
    }

    public function homeUrl(): string
    {
        return route($this->home);
    }

    public function identifierInputName(): string
    {
        return count($this->identifiers) === 1 ? $this->identifiers[0] : 'identifier';
    }

    public function guest(): array
    {
        return ['guest:' . $this->guard];
    }

    public function auth(): array
    {
        $middleware = ['auth:' . $this->guard];

        if ($this->featureEnabled('lock-screen')) {
            $middleware[] = $this->lockedMiddleware();
        }

        return $middleware;
    }

    public function lockedMiddleware(): string
    {
        return Locked::class . ':' . $this->guard . ',' . $this->routeName('unlock');
    }

    protected function resolveDisabled(array $disable): array
    {
        $supported = [
            'register',
            'magic-link',
            'email-verification',
            'logout',
            'lock-screen',
        ];

        $disabled = [];

        foreach ($disable as $feature) {
            if (is_string($feature) && in_array($feature, $supported, true)) {
                $disabled[$feature] = true;
            }
        }

        if (! is_subclass_of($this->model, MustVerifyEmail::class)) {
            $disabled['email-verification'] = true;
        }

        if ($this->api || ! isset($this->views['unlock'])) {
            $disabled['lock-screen'] = true;
        }

        return $disabled;
    }
}

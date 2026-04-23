<?php

namespace Redot;

use Composer\InstalledVersions;
use Exception;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Js;
use Illuminate\Support\ServiceProvider;
use Redot\Auth\RedotAuthServiceProvider;
use Redot\Commands\BuildDependenciesCommand;
use Redot\Commands\ClearUploadsCommand;
use Redot\Commands\EntityMakeCommand;
use Redot\Commands\ExtractLanguageTokensCommand;
use Redot\Commands\LintCommand;
use Redot\Commands\ModelPopulateCommand;
use Redot\Commands\PublicLinkCommand;
use Redot\Commands\PublishLanguageTokensCommand;
use Redot\Commands\RevertLanguageTokensCommand;
use Redot\Commands\SyncLanguageTokensCommand;
use Redot\Commands\SyncPermissionsCommand;
use Redot\Commands\ViewMakeCommand;
use Redot\Datatables\DatatablesServiceProvider;
use Redot\LangExtractor\LaravelLangExtractorServiceProvider;
use Redot\Models\Language;
use Redot\Rules\Captcha;
use Redot\Rules\Phone;
use Redot\Sidebar\Sidebar;
use Redot\Toastify\LaravelToastifyServiceProvider;

class RedotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->config();
        $this->stubs();
        $this->migrations();

        $this->commands([
            BuildDependenciesCommand::class,
            ClearUploadsCommand::class,
            EntityMakeCommand::class,
            ExtractLanguageTokensCommand::class,
            LintCommand::class,
            ModelPopulateCommand::class,
            PublicLinkCommand::class,
            PublishLanguageTokensCommand::class,
            RevertLanguageTokensCommand::class,
            SyncLanguageTokensCommand::class,
            SyncPermissionsCommand::class,
            ViewMakeCommand::class,
        ]);

        $this->app->singleton(Sidebar::class, fn () => new Sidebar);
        $this->app->alias(Sidebar::class, 'sidebar');

        $this->app->register(RedotAuthServiceProvider::class);
        $this->app->register(DatatablesServiceProvider::class);
        $this->app->register(LaravelLangExtractorServiceProvider::class);
        $this->app->register(LaravelToastifyServiceProvider::class);
    }

    /**
     * Register the package configuration.
     */
    protected function config(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/config/redot.php',
            'redot'
        );

        $this->publishes([
            dirname(__DIR__) . '/config/redot.php' => config_path('redot.php'),
        ], 'redot::config');
    }

    /**
     * Register the package stubs.
     */
    protected function stubs(): void
    {
        $this->publishes([
            __DIR__ . '/../stubs/' => base_path('stubs/'),
        ], 'redot::stubs');
    }

    /**
     * Register the package migrations.
     */
    protected function migrations(): void
    {
        $this->publishesMigrations([
            __DIR__ . '/../database/migrations/' => database_path('migrations/'),
        ], 'redot::migrations');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureAboutCommand();

        $this->configureBlade();
        $this->configurePaginatorView();

        $this->configureApiRateLimiter();
        $this->configureConvertEmptyStringToNull();

        $this->configureDestructiveCommands();
        $this->configureApplicationLocales();

        $this->configureValidationRules();
        $this->configureJsonCast();
    }

    /**
     * Configure the about command.
     */
    protected function configureAboutCommand(): void
    {
        AboutCommand::add('Redot', [
            'Version' => InstalledVersions::getPrettyVersion('redot/core'),
            'Website' => 'https://redot.dev',
        ]);
    }

    /**
     * Configure Blade directives and components.
     */
    protected function configureBlade(): void
    {
        Blade::anonymousComponentPath(resource_path('layouts'), 'layouts');
        Blade::componentNamespace('App\\View\\Layouts', 'layouts');

        Blade::directive('themer', function ($expression = 'theme') {
            $path = hashed_asset('assets/js/themer.js');
            $expression = str_replace(['"', "'", '`'], '', $expression);
            $config = Js::encode(setting('theme'));

            return Blade::compileString(
                <<<EOT
                    @push('pre-content')
                        <script>window.themerKey = '$expression';</script>
                        <script>window.themeConfig = $config;</script>
                        <script src="$path"></script>
                    @endpush
                EOT
            );
        });
    }

    /**
     * Configure the default pagination view.
     */
    protected function configurePaginatorView(): void
    {
        Paginator::defaultView('components.pagination');
    }

    /**
     * Configure the application locales.
     */
    protected function configureApplicationLocales(): void
    {
        try {
            config(['app.locales' => Language::pluck('name', 'code')->toArray()]);
        } catch (Exception) {
            config(['app.locales' => array_column(config('redot.locales'), 'name', 'code')]);
        }

        // Set the default locale to the first locale in the locales array
        URL::defaults(['locale' => Arr::first(array_keys(config('app.locales')))]);
    }

    /**
     * Configure the rate limiter for the API.
     */
    protected function configureApiRateLimiter(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }

    /**
     * Configure the conversion of empty strings to null.
     */
    protected function configureConvertEmptyStringToNull(): void
    {
        ConvertEmptyStringsToNull::skipWhen(function (Request $request) {
            return $request->is('*settings*') && $request->isMethod('put');
        });
    }

    /**
     * Configure the destructive commands.
     */
    protected function configureDestructiveCommands(): void
    {
        DB::prohibitDestructiveCommands(app()->environment('production'));
    }

    /**
     * Configure the custom validation rules.
     */
    protected function configureValidationRules(): void
    {
        Validator::extend('phone', function ($attribute, $value, $parameters) {
            return (new Phone(...$parameters))->passes($attribute, $value);
        });

        Validator::extend('captcha', function ($attribute, $value, $parameters) {
            return (new Captcha(...$parameters))->passes($attribute, $value);
        });
    }

    /**
     * Configure the JSON cast.
     */
    protected function configureJsonCast(): void
    {
        Json::encodeUsing(function ($value) {
            if (is_array($value)) {
                return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            return $value;
        });

        Json::decodeUsing(function ($value) {
            if (is_string($value)) {
                return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            }

            return $value;
        });
    }
}

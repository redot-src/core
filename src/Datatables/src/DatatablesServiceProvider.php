<?php

namespace Redot\Datatables;

use Illuminate\Support\ServiceProvider;
use Redot\Datatables\Commands\DatatableMakeCommand;
use Redot\Datatables\Commands\DatatablesLinkCommand;

class DatatablesServiceProvider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/datatables.php', 'datatables');
    }

    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/datatables.php' => config_path('datatables.php'),
        ], 'datatables::config');

        $this->views();
        $this->lang();
        $this->assets();

        $this->commands([
            DatatableMakeCommand::class,
            DatatablesLinkCommand::class,
        ]);
    }

    /**
     * Register the package views.
     */
    protected function views(): void
    {
        $this->loadViewsFrom(
            __DIR__ . '/../resources/views',
            'datatables'
        );

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/datatables'),
        ], 'datatables::views');
    }

    /**
     * Register the package language files.
     */
    protected function lang(): void
    {
        $this->loadTranslationsFrom(
            __DIR__ . '/../lang',
            'datatables'
        );

        $this->publishes([
            __DIR__ . '/../lang' => lang_path('vendor/datatables'),
        ], 'datatables::lang');
    }

    /**
     * Register the package public assets.
     */
    protected function assets(): void
    {
        $this->publishes([
            __DIR__ . '/../public' => public_path('vendor/datatables'),
        ], 'datatables::assets');
    }
}

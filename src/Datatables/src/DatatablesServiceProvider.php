<?php

namespace Redot\Datatables;

use Illuminate\Support\ServiceProvider;
use Redot\Datatables\Commands\DatatableMakeCommand;

class DatatablesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->views();
        $this->lang();
        $this->assets();

        $this->commands([
            DatatableMakeCommand::class,
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
        ], 'redot::datatables-views');
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
        ], 'redot::datatables-lang');
    }

    /**
     * Register the package public assets.
     */
    protected function assets(): void
    {
        $this->publishes([
            __DIR__ . '/../public' => public_path('vendor/redot/datatables'),
        ], 'redot::assets');
    }
}

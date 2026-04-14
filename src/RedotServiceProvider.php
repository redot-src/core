<?php

namespace Redot;

use Illuminate\Support\ServiceProvider;
use Redot\Auth\RedotAuthServiceProvider;
use Redot\Datatables\DatatablesServiceProvider;
use Redot\LangExtractor\LaravelLangExtractorServiceProvider;
use Redot\Sidebar\Sidebar;
use Redot\Toastify\LaravelToastifyServiceProvider;

class RedotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Sidebar::class, fn () => new Sidebar);
        $this->app->alias(Sidebar::class, 'sidebar');

        $this->app->register(RedotAuthServiceProvider::class);
        $this->app->register(DatatablesServiceProvider::class);
        $this->app->register(LaravelLangExtractorServiceProvider::class);
        $this->app->register(LaravelToastifyServiceProvider::class);
    }
}

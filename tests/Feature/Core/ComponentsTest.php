<?php

use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\ViewErrorBag;
use Redot\Datatables\DatatablesServiceProvider;
use Redot\Models\Language;
use Redot\RedotServiceProvider;

it('renders flashed toasts through the package toasts component', function () {
    session()->put('toastify', [
        ['type' => 'success', 'title' => 'Saved', 'message' => null, 'options' => []],
    ]);

    $html = Blade::render("<x-toasts />@stack('plugins-scripts')");

    expect($html)
        ->toContain('/vendor/redot/plugins/redot-toasts.js')
        ->toContain('new RedotToasts(')
        ->toContain('"title":"Saved"')
        ->and(session()->has('toastify'))->toBeFalse();
});

it('lets an application view override a package component view', function () {
    View::getFinder()->prependLocation(dirname(__DIR__, 2) . '/Fixtures/views');

    expect(Blade::render('<x-toasts />'))->toContain('application toasts');
});

it('publishes every package asset under public/vendor/redot', function () {
    $resolve = fn (string $provider) => collect(ServiceProvider::pathsToPublish($provider, 'redot::assets'))
        ->mapWithKeys(fn ($target, $source) => [realpath($source) => $target])
        ->all();

    expect($resolve(RedotServiceProvider::class))->toBe([
        realpath(dirname(__DIR__, 3) . '/src/Foundation/public') => public_path('vendor/redot'),
    ])->and($resolve(DatatablesServiceProvider::class))->toBe([
        realpath(dirname(__DIR__, 3) . '/src/Datatables/public') => public_path('vendor/redot/datatables'),
    ]);
});

it('replaces the published assets with the redot publish command', function () {
    $published = public_path('vendor/redot');
    File::ensureDirectoryExists($published);
    File::put($published . '/stale.js', 'stale');

    try {
        $this->artisan('redot:publish')->assertSuccessful();

        expect($published . '/stale.js')->not->toBeFile()
            ->and($published . '/plugins/redot-toasts.js')->toBeFile()
            ->and($published . '/libraries/tom-select/tom-select.complete.min.js')->toBeFile()
            ->and($published . '/datatables/datatables.js')->toBeFile();
    } finally {
        File::deleteDirectory($published);
    }
});

it('renders the redot styles in order with the current language direction', function () {
    Language::create(['code' => 'ar', 'name' => 'Arabic', 'is_rtl' => true]);
    app()->setLocale('ar');

    $html = Blade::render("@push('plugins-styles')<link href=\"plugin.css\" />@endpush<x-redot-styles />");

    expect($html)->toContain('/vendor/redot/libraries/tabler/css/tabler.rtl.min.css')
        ->and(strpos($html, 'overlayscrollbars.min.css'))->toBeLessThan(strpos($html, 'plugin.css'))
        ->and(strpos($html, 'plugin.css'))->toBeLessThan(strpos($html, 'tabler-vendors.min.css'))
        ->and(strpos($html, 'tabler-vendors.min.css'))->toBeLessThan(strpos($html, '/vendor/redot/css/redot.css'));
});

it('initializes widgets before restoring validation errors', function () {
    View::share('errors', (new ViewErrorBag)->put('default', new MessageBag(['email' => ['Invalid']])));

    $html = Blade::render('<x-redot-scripts />');

    expect(strpos($html, '/vendor/redot/libraries/jquery/jquery.min.js'))->toBeLessThan(strpos($html, '/vendor/redot/js/functions.js'))
        ->and(strpos($html, '/assets/dist/init.js'))->toBeLessThan(strpos($html, 'window.OldBag'))
        ->and(strpos($html, '/vendor/redot/js/runtime.js'))->toBeLessThan(strpos($html, 'appendErrorsToForm($form, window.ErrorsBag)'));
});

it('skips the error restoration script when there are no errors', function () {
    expect(Blade::render('<x-redot-scripts />'))
        ->toContain('window.ErrorsBag')
        ->not->toContain('appendErrorsToForm');
});

it('loads the toasts plugin before initializing it when nested components push scripts first', function () {
    $html = Blade::render("<x-uploader name=\"files\" /><x-redot-scripts />@stack('plugins-scripts')");

    expect(strpos($html, '/vendor/redot/plugins/redot-toasts.js'))->toBeLessThan(strpos($html, 'new RedotToasts('));
});

it('passes the endpoint and asset urls to the scripts so subdirectory installs work', function () {
    URL::forceRootUrl('http://example.test/panel');

    expect(Blade::render('<x-uploader name="files" />'))->toContain('uploader-endpoint="http://example.test/panel/uploader/upload"')
        ->and(Blade::render('<x-rich-editor name="body" />'))->toContain('hugerte-upload-url="http://example.test/panel/hugerte/upload"')
        ->and(Blade::render('<x-icon-picker name="icon" />'))->toContain('iconpicker-source="http://example.test/panel/vendor/redot/libraries/tabler-icons/tabler-icons.json');
});

it('turns off rich editor uploads when the hugerte endpoint is disabled', function () {
    config(['redot.components.hugerte.enabled' => false]);
    Route::setRoutes(new RouteCollection);
    $this->defineRoutes(app('router'));
    Route::getRoutes()->refreshNameLookups();

    expect(Blade::render('<x-rich-editor name="body" />'))->toContain('init="hugerte"')->not->toContain('hugerte-upload-url');
});

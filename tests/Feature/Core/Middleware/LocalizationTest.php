<?php

use Illuminate\Support\Facades\Route;
use Redot\Http\Middleware\Localization;

it('sets the application locale from the route parameter', function () {
    Route::middleware(Localization::class)
        ->get('/{locale}/localized-probe', fn () => response(app()->getLocale() . '|' . request()->route('locale', 'missing')))
        ->name('website.localized-probe');

    $this->get('/ar/localized-probe')
        ->assertOk()
        ->assertSee('ar|missing');

    expect(session('website_locale'))->toBe('ar')
        ->and(app()->getLocale())->toBe('ar');
});

it('redirects unsupported route locales to the fallback locale', function () {
    Route::middleware(Localization::class)
        ->get('/{locale}/fallback-probe', fn () => response('ok'))
        ->name('website.fallback-probe');

    $this->get('/fr/fallback-probe?foo=bar')
        ->assertRedirect('/en/fallback-probe?foo=bar')
        ->assertStatus(301);
});

it('lets the locale query string override the route locale', function () {
    Route::middleware(Localization::class)
        ->get('/{locale}/query-locale-probe', fn () => response('ok'))
        ->name('website.query-locale-probe');

    $this->get('/en/query-locale-probe?locale=ar')
        ->assertRedirect('/ar/query-locale-probe?locale=ar')
        ->assertStatus(301);
});

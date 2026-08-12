<?php

use Illuminate\Support\Facades\Route;
use Redot\Http\Middleware\Localization;

it('sets the application locale from the route parameter', function () {
    Route::middleware(Localization::class . ':website')
        ->get('/{locale}/localized-probe', fn () => response(app()->getLocale() . '|' . request()->route('locale', 'missing')))
        ->name('website.localized-probe');

    $this->get('/ar/localized-probe')
        ->assertOk()
        ->assertSee('ar|missing')
        ->assertPlainCookie('website_locale', 'ar');

    expect(session('website_locale'))->toBe('ar')
        ->and(app()->getLocale())->toBe('ar');
});

it('redirects unsupported route locales to the fallback locale and preserves the query string', function () {
    Route::middleware(Localization::class . ':website')
        ->get('/{locale}/fallback-probe', fn () => response('ok'))
        ->name('website.fallback-probe');

    $this->get('/fr/fallback-probe?foo=bar')
        ->assertRedirect('/en/fallback-probe?foo=bar')
        ->assertStatus(301);
});

it('lets the locale query string override the route locale', function () {
    Route::middleware(Localization::class . ':website')
        ->get('/{locale}/query-locale-probe', fn () => response('ok'))
        ->name('website.query-locale-probe');

    $this->get('/en/query-locale-probe?locale=ar')
        ->assertRedirect('/ar/query-locale-probe?locale=ar')
        ->assertStatus(301);
});

it('stores the active locale in the dashboard-scoped session key when the dashboard scope is passed', function () {
    Route::middleware(Localization::class . ':dashboard')
        ->get('/{locale}/dashboard/scope-probe', fn () => response(app()->getLocale()))
        ->name('dashboard.scope-probe');

    $this->get('/ar/dashboard/scope-probe')
        ->assertOk()
        ->assertSee('ar')
        ->assertPlainCookie('dashboard_locale', 'ar')
        ->assertCookieMissing('website_locale');

    expect(session('dashboard_locale'))->toBe('ar')
        ->and(session('website_locale'))->toBeNull();
});

it('falls back to the last language from the cookie when the route locale is not allowed', function () {
    Route::middleware(Localization::class . ':website')
        ->get('/{locale}/cookie-fallback-probe', fn () => response('ok'))
        ->name('website.cookie-fallback-probe');

    $this->withUnencryptedCookie('website_locale', 'ar')
        ->get('/fr/cookie-fallback-probe?foo=bar')
        ->assertRedirect('/ar/cookie-fallback-probe?foo=bar')
        ->assertStatus(301)
        ->assertPlainCookie('website_locale', 'ar');
});

it('falls back to the last language from the session when the route locale is not allowed', function () {
    Route::middleware(Localization::class . ':website')
        ->get('/{locale}/session-fallback-probe', fn () => response('ok'))
        ->name('website.session-fallback-probe');

    $this->withSession(['website_locale' => 'ar'])
        ->get('/fr/session-fallback-probe')
        ->assertRedirect('/ar/session-fallback-probe')
        ->assertStatus(301);
});

it('lets a valid route locale override the stored cookie language', function () {
    Route::middleware(Localization::class . ':website')
        ->get('/{locale}/cookie-override-probe', fn () => response(app()->getLocale()))
        ->name('website.cookie-override-probe');

    $this->withUnencryptedCookie('website_locale', 'ar')
        ->get('/en/cookie-override-probe')
        ->assertOk()
        ->assertSee('en')
        ->assertPlainCookie('website_locale', 'en');
});

it('uses the passed scope even when the route name looks like the other scope', function () {
    Route::middleware(Localization::class . ':website')
        ->get('/{locale}/dashboard/explicit-scope-probe', fn () => response(app()->getLocale()))
        ->name('dashboard.explicit-scope-probe');

    $this->get('/ar/dashboard/explicit-scope-probe')
        ->assertOk()
        ->assertSee('ar')
        ->assertPlainCookie('website_locale', 'ar')
        ->assertCookieMissing('dashboard_locale');

    expect(session('website_locale'))->toBe('ar')
        ->and(session('dashboard_locale'))->toBeNull();
});

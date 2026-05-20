<?php

use Redot\Models\Language;

it('exposes a left-to-right or right-to-left direction derived from the rtl flag', function () {
    expect(Language::make(['is_rtl' => false])->direction)->toBe('ltr')
        ->and(Language::make(['is_rtl' => true])->direction)->toBe('rtl');
});

it('resolves the current language from the active application locale', function () {
    Language::create(['code' => 'en', 'name' => 'English', 'is_rtl' => false]);
    Language::create(['code' => 'ar', 'name' => 'Arabic', 'is_rtl' => true]);

    app()->setLocale('ar');

    expect(Language::current())
        ->toBeInstanceOf(Language::class)
        ->code->toBe('ar')
        ->direction->toBe('rtl');
});

it('uses the language code as the route key for implicit route-model binding', function () {
    expect((new Language)->getRouteKeyName())->toBe('code');
});

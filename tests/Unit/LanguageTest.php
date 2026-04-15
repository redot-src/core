<?php

use Redot\Models\Language;

it('resolves the current language and exposes its direction', function () {
    Language::create([
        'code' => 'en',
        'name' => 'English',
        'is_rtl' => false,
    ]);

    $arabic = Language::create([
        'code' => 'ar',
        'name' => 'Arabic',
        'is_rtl' => true,
    ]);

    app()->setLocale('ar');

    expect(Language::current()?->is($arabic))->toBeTrue()
        ->and($arabic->direction)->toBe('rtl')
        ->and($arabic->getRouteKeyName())->toBe('code');
});

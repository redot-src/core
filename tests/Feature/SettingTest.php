<?php

use Redot\Models\Setting;

it('falls back to configuration defaults when a setting is missing', function () {
    expect(setting('app_logo_dark'))->toBe('assets/images/logo-dark.svg')
        ->and(Setting::default('app_name.en'))->toBe('Dashboard')
        ->and(Setting::rules())->toHaveKey('app_name');
});

it('persists structured setting values and resolves nested keys', function () {
    $data = [
        'en' => 'Control Center',
        'ar' => 'Markaz',
    ];

    Setting::set('app_name', $data);

    expect(setting('app_name'))->toBe($data)
        ->and(setting('app_name.en'))->toBe('Control Center')
        ->and(setting('app_name.ar'))->toBe('Markaz');
});

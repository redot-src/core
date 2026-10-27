<?php

use Redot\View\Components\Translatable;

test('only the main locale is required while other validation rules apply to every locale', function () {
    $component = new Translatable(
        validation: 'required | email',
        locales: ['en', 'ar'],
        validateOnlyMainLocale: true,
    );

    $component->render();

    expect($component->localesConfig)
        ->toHaveKey('en.validation', 'required | email')
        ->toHaveKey('ar.validation', 'email');
});

test('validate locales controls only the required rule', function () {
    $component = new Translatable(
        validation: 'required|email|max:255',
        locales: ['en', 'ar', 'fr'],
        validateLocales: ['ar'],
    );

    $component->render();

    expect($component->localesConfig)
        ->toHaveKey('en.validation', 'email|max:255')
        ->toHaveKey('ar.validation', 'required|email|max:255')
        ->toHaveKey('fr.validation', 'email|max:255');
});

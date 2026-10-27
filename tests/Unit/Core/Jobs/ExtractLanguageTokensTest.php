<?php

use Redot\Jobs\ExtractLanguageTokens;
use Redot\Models\Language;

it('extracts tokens from the package views and scripts', function () {
    $language = Language::create(['code' => 'fr', 'name' => 'French', 'is_rtl' => false]);

    (new ExtractLanguageTokens($language))->handle();

    expect($language->tokens()->where('key', 'Close')->exists())->toBeTrue();
});

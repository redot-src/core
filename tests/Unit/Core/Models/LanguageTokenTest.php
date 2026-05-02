<?php

use Redot\Models\Language;
use Redot\Models\LanguageToken;

it('casts token flags and belongs to a language', function () {
    $language = Language::create(['code' => 'en', 'name' => 'English', 'is_rtl' => false]);

    $token = LanguageToken::create([
        'language_id' => $language->id,
        'key' => 'Hello',
        'value' => 'Hello',
        'original_translation' => 'Hello',
        'from_json' => 1,
        'is_published' => 1,
    ]);

    expect($token->from_json)->toBeTrue()
        ->and($token->is_published)->toBeTrue()
        ->and($token->language->is($language))->toBeTrue();
});

it('marks published tokens as unpublished when their value changes', function () {
    $language = Language::create(['code' => 'en', 'name' => 'English', 'is_rtl' => false]);
    $token = LanguageToken::create([
        'language_id' => $language->id,
        'key' => 'Hello',
        'value' => 'Hello',
        'original_translation' => 'Hello',
        'from_json' => true,
        'is_published' => true,
    ]);

    $token->update(['value' => 'Hi']);

    expect($token->refresh()->is_published)->toBeFalse();
});

it('scopes language tokens by state', function () {
    $language = Language::create(['code' => 'en', 'name' => 'English', 'is_rtl' => false]);

    LanguageToken::create([
        'language_id' => $language->id,
        'key' => 'Published',
        'value' => 'Published',
        'original_translation' => 'Published',
        'from_json' => true,
        'is_published' => true,
    ]);

    LanguageToken::create([
        'language_id' => $language->id,
        'key' => 'Modified',
        'value' => 'Changed',
        'original_translation' => 'Original',
        'from_json' => false,
        'is_published' => false,
    ]);

    expect(LanguageToken::published()->count())->toBe(1)
        ->and(LanguageToken::unpublished()->count())->toBe(1)
        ->and(LanguageToken::modified()->count())->toBe(1)
        ->and(LanguageToken::notModified()->count())->toBe(1)
        ->and(LanguageToken::query()->fromJson()->count())->toBe(1)
        ->and(LanguageToken::query()->notFromJson()->count())->toBe(1);
});

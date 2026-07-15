<?php

use Illuminate\Support\Facades\File;
use Redot\Jobs\PublishLanguageTokens;
use Redot\Models\Language;

it('publishes translations to lowercase locale files using valid php escaping', function () {
    $langPath = sys_get_temp_dir() . '/redot-language-publish-' . uniqid();
    File::ensureDirectoryExists($langPath . '/ar');
    File::put($langPath . '/ar/messages.php', <<<'PHP'
<?php

return [
    'greeting+' => 'Arabic original',
];
PHP);

    app()->useLangPath($langPath);
    app()->setLocale('en');
    app('translator')->addLines(['messages.greeting+' => 'English original'], 'en');
    app('translator')->addLines(['messages.greeting+' => 'Arabic original'], 'ar');

    $language = Language::create(['code' => 'AR', 'name' => 'Arabic', 'is_rtl' => true]);
    $token = $language->tokens()->create([
        'key' => 'messages.greeting+',
        'value' => 'A "double", single \' and \\ slash',
        'original_translation' => 'Arabic original',
        'from_json' => false,
        'is_published' => false,
    ]);
    $missingToken = $language->tokens()->create([
        'key' => 'messages.missing',
        'value' => 'Still missing',
        'original_translation' => 'Missing original',
        'from_json' => false,
        'is_published' => false,
    ]);

    try {
        (new PublishLanguageTokens($language))->handle();

        $translations = require $langPath . '/ar/messages.php';

        expect($translations['greeting+'])->toBe('A "double", single \' and \\ slash')
            ->and($language->refresh()->code)->toBe('AR')
            ->and($token->refresh()->is_published)->toBeTrue()
            ->and($missingToken->refresh()->is_published)->toBeFalse();
    } finally {
        File::deleteDirectory($langPath);
    }
});

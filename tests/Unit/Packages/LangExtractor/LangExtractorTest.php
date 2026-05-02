<?php

use Illuminate\Support\Facades\File;
use Redot\LangExtractor\LangExtractor;

it('extracts unique translation strings from configured directories', function () {
    $directory = sys_get_temp_dir() . '/redot-lang-extractor';

    File::deleteDirectory($directory);
    File::ensureDirectoryExists($directory);
    File::put($directory . '/view.blade.php', "{{ __('Hello world') }} @lang('Dashboard') {{ __('Hello world') }}");
    File::put($directory . '/component.php', "<?php echo trans('Saved successfully');");

    $translations = (new LangExtractor([$directory], ['php', 'blade.php']))
        ->withExtensions('php', 'blade.php')
        ->extract()
        ->all();

    expect($translations)->toHaveCount(3)
        ->and($translations)->toMatchArray([
            'Hello world' => 'Hello world',
            'Dashboard' => 'Dashboard',
            'Saved successfully' => 'Saved successfully',
        ]);
});

it('merges extracted translations with arrays and existing json files', function () {
    $directory = sys_get_temp_dir() . '/redot-lang-extractor-merge';
    $path = sys_get_temp_dir() . '/redot-translations.json';

    File::deleteDirectory($directory);
    File::ensureDirectoryExists($directory);
    File::put($directory . '/view.php', "<?php __('New key');");
    File::put($path, json_encode(['Existing key' => 'Existing value']));

    $translations = (new LangExtractor([$directory], ['php']))
        ->extract()
        ->mergeWithArray(['Array key' => 'Array value'])
        ->mergeWithFile($path)
        ->all();

    expect($translations)->toBe([
        'Array key' => 'Array value',
        'Existing key' => 'Existing value',
        'New key' => 'New key',
    ]);
});

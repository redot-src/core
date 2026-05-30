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

it('extracts translations from the double underscore helper', function () {
    $translations = (new LangExtractor)->extractFromString(<<<'PHP'
        <?php

        __('Hello from string');
        __("Saved from string");
        __('Hello from string');
        PHP);

    expect($translations)->toBe([
        'Hello from string' => 'Hello from string',
        'Saved from string' => 'Saved from string',
    ]);
});

it('extracts translations from the trans helper', function () {
    $translations = (new LangExtractor)->extractFromString(<<<'PHP'
        <?php

        trans('Publish post');
        trans("Delete post");
        PHP);

    expect($translations)->toBe([
        'Publish post' => 'Publish post',
        'Delete post' => 'Delete post',
    ]);
});

it('extracts translations from the lang blade directive', function () {
    $translations = (new LangExtractor)->extractFromString(<<<'BLADE'
        @lang('Dashboard label')
        @lang("Save changes")
        BLADE);

    expect($translations)->toBe([
        'Dashboard label' => 'Dashboard label',
        'Save changes' => 'Save changes',
    ]);
});

it('extracts translations containing escaped quotes', function () {
    $translations = (new LangExtractor)->extractFromString(<<<'PHP'
        <?php

        __('It\'s ready');
        __("Click \"Save\"");
        PHP);

    expect($translations)->toBe([
        "It's ready" => "It's ready",
        'Click "Save"' => 'Click "Save"',
    ]);
});

it('extracts translations with replacement parameters', function () {
    $translations = (new LangExtractor)->extractFromString(<<<'PHP'
        <?php

        __('Welcome, :name', ['name' => $user->name]);
        trans('Published :count posts', ['count' => $posts->count()]);
        @lang('Last updated at :time', ['time' => $updatedAt])
        PHP);

    expect($translations)->toBe([
        'Welcome, :name' => 'Welcome, :name',
        'Published :count posts' => 'Published :count posts',
        'Last updated at :time' => 'Last updated at :time',
    ]);
});

it('extracts nested translations used as replacement parameters', function () {
    $translations = (new LangExtractor)->extractFromString(<<<'PHP'
        <?php

        __('Delete :resource?', ['resource' => __('Post')]);
        trans('Saved :resource', ['resource' => trans('Profile')]);
        @lang('Viewing :resource', ['resource' => __('Dashboard')])
        PHP);

    expect($translations)->toBe([
        'Delete :resource?' => 'Delete :resource?',
        'Post' => 'Post',
        'Saved :resource' => 'Saved :resource',
        'Profile' => 'Profile',
        'Viewing :resource' => 'Viewing :resource',
        'Dashboard' => 'Dashboard',
    ]);
});

it('extracts translations from multiline calls', function () {
    $translations = (new LangExtractor)->extractFromString(<<<'PHP'
        <?php

        __(
            'Create user'
        );
        trans(
            "Update user",
            ['name' => $user->name],
        );
        @lang(
            'Delete user',
            ['name' => $user->name],
        )
        PHP);

    expect($translations)->toBe([
        'Create user' => 'Create user',
        'Update user' => 'Update user',
        'Delete user' => 'Delete user',
    ]);
});

it('extracts translations containing multiline strings', function () {
    $translations = (new LangExtractor)->extractFromString(<<<'PHP'
        <?php

        __('First line
        Second line');
        trans("Third line
        Fourth line");
        PHP);

    expect($translations)->toBe([
        "First line\nSecond line" => "First line\nSecond line",
        "Third line\nFourth line" => "Third line\nFourth line",
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

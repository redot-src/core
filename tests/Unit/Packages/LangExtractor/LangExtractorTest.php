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

it('extracts translations from the pluralization helpers', function () {
    $translations = (new LangExtractor)->extractFromString(<<<'PHP'
        <?php

        trans_choice('One apple|Many apples', 5);
        @choice('One user|Many users', 3)
        PHP);

    expect($translations)->toBe([
        'One apple|Many apples' => 'One apple|Many apples',
        'One user|Many users' => 'One user|Many users',
    ]);
});

it('extracts translations from the lang facade', function () {
    $translations = (new LangExtractor)->extractFromString(<<<'PHP'
        <?php

        Lang::get('Server error');
        Lang::choice('One post|Many posts', 2);
        \Illuminate\Support\Facades\Lang::get('Fully qualified error');
        PHP);

    expect($translations)->toBe([
        'Server error' => 'Server error',
        'One post|Many posts' => 'One post|Many posts',
        'Fully qualified error' => 'Fully qualified error',
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

it('extracts translations ending with an escaped backslash', function () {
    $translations = (new LangExtractor)->extractFromString(<<<'PHP'
        <?php

        __('Windows path C:\\');
        __("Trailing backslash \\");
        __('Following key');
        PHP);

    expect($translations)->toBe([
        'Windows path C:\\' => 'Windows path C:\\',
        'Trailing backslash \\' => 'Trailing backslash \\',
        'Following key' => 'Following key',
    ]);
});

it('decodes escape sequences the same way php does', function () {
    $translations = (new LangExtractor)->extractFromString(<<<'PHP'
        <?php

        __("First line\nSecond line");
        __("First column\tSecond column");
        __("Unicode \u{00e9} and hex \x41");
        __('Literal \n stays as is');
        PHP);

    expect($translations)->toBe([
        "First line\nSecond line" => "First line\nSecond line",
        "First column\tSecond column" => "First column\tSecond column",
        'Unicode é and hex A' => 'Unicode é and hex A',
        'Literal \n stays as is' => 'Literal \n stays as is',
    ]);
});

it('extracts translations from heredoc and nowdoc arguments', function () {
    $translations = (new LangExtractor)->extractFromString(<<<'PHP'
        <?php

        __(<<<'NOWDOC'
            Terms and conditions
            NOWDOC);

        trans(<<<HEREDOC
            Privacy policy
            HEREDOC);
        PHP);

    expect($translations)->toBe([
        'Terms and conditions' => 'Terms and conditions',
        'Privacy policy' => 'Privacy policy',
    ]);
});

it('extracts translations preceded by comments or named arguments', function () {
    $translations = (new LangExtractor)->extractFromString(<<<'PHP'
        <?php

        __(/* label */ 'Save changes');
        __( // label
            'Cancel changes'
        );
        __(key: 'Named argument');
        PHP);

    expect($translations)->toBe([
        'Save changes' => 'Save changes',
        'Cancel changes' => 'Cancel changes',
        'Named argument' => 'Named argument',
    ]);
});

it('joins concatenated string literals', function () {
    $translations = (new LangExtractor)->extractFromString(<<<'PHP'
        <?php

        __('This is a long '
            . 'sentence');
        __("Joined " . 'quotes');
        PHP);

    expect($translations)->toBe([
        'This is a long sentence' => 'This is a long sentence',
        'Joined quotes' => 'Joined quotes',
    ]);
});

it('ignores comments while joining concatenated string literals', function () {
    $translations = (new LangExtractor)->extractFromString(<<<'PHP'
        <?php

        __('First ' /* 'ignored' */ . 'translation');
        __("Second " // "ignored"
            . "translation");
        PHP);

    expect($translations)->toBe([
        'First translation' => 'First translation',
        'Second translation' => 'Second translation',
    ]);
});

it('skips arguments that are not fully literal', function () {
    $translations = (new LangExtractor)->extractFromString(<<<'PHP'
        <?php

        __($label);
        __(self::LABEL);
        __('Prefix ' . $suffix);
        trans($prefix . ' suffix');
        PHP);

    expect($translations)->toBe([]);
});

it('skips interpolated strings', function () {
    $translations = (new LangExtractor)->extractFromString(<<<'PHP'
        <?php

        __("Hello $name");
        __("Hello {$user->name}");
        __(<<<HEREDOC
            Heredoc $name
            HEREDOC);
        PHP);

    expect($translations)->toBe([]);
});

it('skips literal prefixes of non-literal expressions', function () {
    $translations = (new LangExtractor)->extractFromString(<<<'PHP'
        <?php

        __('Condition' ? 'Enabled' : 'Disabled');
        __('Fallback' ?? $fallback);
        __('Compared' === $value);
        PHP);

    expect($translations)->toBe([]);
});

it('skips escaped blade directives', function () {
    $translations = (new LangExtractor)->extractFromString(<<<'BLADE'
        @@lang('Escaped directive')
        @lang('Real directive')
        BLADE);

    expect($translations)->toBe([
        'Real directive' => 'Real directive',
    ]);
});

it('keeps padded and falsy translation keys untouched', function () {
    $translations = (new LangExtractor)->extractFromString(<<<'PHP'
        <?php

        __('  Padded label  ');
        __('0');
        __('');
        PHP);

    expect($translations)->toBe([
        '  Padded label  ' => '  Padded label  ',
        '0' => '0',
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

it('skips file based keys without dropping sentences that start with a group name', function () {
    $directory = sys_get_temp_dir() . '/redot-lang-extractor-groups';

    File::deleteDirectory($directory);
    File::ensureDirectoryExists($directory . '/en');
    File::put($directory . '/en/validation.php', '<?php return [];');
    File::put($directory . '/en/invoice(v2).php', '<?php return [];');

    app()->useLangPath($directory);
    config(['app.fallback_locale' => 'en']);

    $translations = (new LangExtractor)->extractFromString(<<<'PHP'
        <?php

        __('validation.required');
        __('invoice(v2).number');
        __('validation.rules are strict here');
        __('Authentication failed');
        PHP);

    expect($translations)->toBe([
        'validation.rules are strict here' => 'validation.rules are strict here',
        'Authentication failed' => 'Authentication failed',
    ]);
});

it('ignores directories that do not exist', function () {
    $directory = sys_get_temp_dir() . '/redot-lang-extractor-existing';
    $missing = sys_get_temp_dir() . '/redot-lang-extractor-missing';

    File::deleteDirectory($directory);
    File::deleteDirectory($missing);
    File::ensureDirectoryExists($directory);
    File::put($directory . '/view.php', "<?php __('Only key');");

    $translations = (new LangExtractor([$missing, $directory], ['php']))->extract()->all();

    expect($translations)->toBe(['Only key' => 'Only key'])
        ->and((new LangExtractor([$missing], ['php']))->extract()->all())->toBe([]);
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

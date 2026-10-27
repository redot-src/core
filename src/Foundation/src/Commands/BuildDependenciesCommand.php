<?php

namespace Redot\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\info;

class BuildDependenciesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dependencies:build';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build the dependencies for the application.';

    /**
     * The dependencies that have been built.
     *
     * @var array
     */
    protected $dependencies = [
        'files' => [],
        'directories' => [],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->buildLanguageFiles();
        $this->buildInitFiles();
        $this->buildLockFile();

        info('Dependencies built successfully!');
    }

    /**
     * Build the language files.
     */
    protected function buildLanguageFiles()
    {
        $this->dependsOnDirectory(lang_path());

        $languages = array_keys(config('app.locales'));
        foreach ($languages as $language) {
            $this->buildLanguageFile($language);
        }
    }

    /**
     * Build the language file for the given language.
     */
    protected function buildLanguageFile(string $locale)
    {
        $locale = strtolower($locale);
        $path = lang_path($locale . '.json');
        $translations = [];

        if (File::exists($path)) {
            $translations = json_decode(File::get($path) ?: '{}', true);

            // Push path to the dependencies array.
            $this->dependsOnFile($path);
        }

        if (File::isDirectory(lang_path($locale))) {
            $this->dependsOnDirectory(lang_path($locale));
        }

        foreach (glob(lang_path($locale . '/*.php')) as $file) {
            // Push path to the dependencies array.
            $this->dependsOnFile($file);

            $basename = basename($file, '.php');

            $translations = array_merge($translations, Arr::dot([
                $basename => require $file,
            ]));
        }

        // Encode the translations in a way that JavaScript can understand.
        $translations = json_encode($translations, JSON_UNESCAPED_UNICODE);

        $javascriptFile = "window.__locale = '" . $locale . "';\n";
        $javascriptFile .= 'window.__translations = ' . $translations . ";\n";

        // Write the JavaScript file.
        $path = dist_path('translations');

        File::ensureDirectoryExists($path);
        File::put($path . '/' . $locale . '.js', $javascriptFile);
    }

    /**
     * Build the init files.
     */
    protected function buildInitFiles()
    {
        $inits = [];

        // Application inits override the package inits with the same name.
        foreach ([dirname(__DIR__, 2) . '/resources/inits', public_path('assets/inits')] as $directory) {
            if (! File::isDirectory($directory)) continue;

            // Push path to the dependencies array.
            $this->dependsOnDirectory($directory);

            foreach (glob($directory . '/*.js') as $file) {
                $inits[basename($file, '.js')] = $file;
            }
        }

        $this->dependsOnFile(...array_values($inits));

        $javascriptFile = 'window.__inits = {};' . "\n";
        foreach ($inits as $name => $file) {
            $javascriptFile .= sprintf(
                'window.__inits["%s"] = (() => { %s })();',
                $name,
                File::get($file)
            );
        }

        // Write the JavaScript file.
        $path = dist_path();

        File::ensureDirectoryExists($path);
        File::put($path . '/init.js', $javascriptFile);
    }

    /**
     * Add a dependency on multiple files.
     */
    protected function dependsOnFile(string ...$files)
    {
        $this->dependencies['files'] = array_merge($this->dependencies['files'], $files);
    }

    /**
     * Add a dependency on multiple directories.
     */
    protected function dependsOnDirectory(string ...$directories)
    {
        $this->dependencies['directories'] = array_merge($this->dependencies['directories'], $directories);
    }

    /**
     * Build the lock file.
     */
    protected function buildLockFile()
    {
        $path = dist_path('lock.json');

        $lock = [
            'files' => [],
            'directories' => [],
        ];

        foreach ($this->dependencies['files'] as $file) {
            $key = str_replace(base_path(), '', $file);
            $lock['files'][$key] = filemtime($file);
        }

        foreach ($this->dependencies['directories'] as $directory) {
            $key = str_replace(base_path(), '', $directory);
            $lock['directories'][$key] = filemtime($directory);
        }

        // Write the lock file.
        File::ensureDirectoryExists(dirname($path));
        File::replace($path, json_encode($lock, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}

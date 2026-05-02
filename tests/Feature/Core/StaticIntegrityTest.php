<?php

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

function package_root(): string
{
    return dirname(__DIR__, 3);
}

it('autoloads all package classes declared under composer psr-4 roots', function () {
    $root = package_root();
    $composer = json_decode(File::get($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    $prefixes = $composer['autoload']['psr-4'];
    $autoloadFiles = collect($composer['autoload']['files'] ?? [])
        ->map(fn (string $path): string => realpath($root . '/' . $path))
        ->filter()
        ->all();
    $packagePaths = collect($prefixes)
        ->reject(fn (string $path, string $namespace): bool => $namespace === 'Redot\\')
        ->map(fn (string $path): string => realpath($root . '/' . $path))
        ->filter()
        ->all();

    foreach ($prefixes as $namespace => $path) {
        foreach (File::allFiles($root . '/' . $path) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            if (in_array(realpath($file->getPathname()), $autoloadFiles, true)) {
                continue;
            }

            if ($namespace === 'Redot\\' && Str::startsWith($file->getPathname(), $root . '/src/packages')) {
                continue;
            }

            $relative = Str::of($file->getRelativePathname())
                ->replace(DIRECTORY_SEPARATOR, '\\')
                ->replaceEnd('.php', '');

            $class = $namespace . $relative;

            expect(class_exists($class) || interface_exists($class) || trait_exists($class))
                ->toBeTrue("Expected [$class] to autoload.");
        }
    }
});

it('keeps command classes concrete and named', function () {
    foreach (File::allFiles(package_root() . '/src/Commands') as $file) {
        $class = 'Redot\\Commands\\' . $file->getBasename('.php');
        $reflection = new ReflectionClass($class);

        expect($reflection->isSubclassOf(Command::class))->toBeTrue("$class must extend Command.");

        $instance = app($class);
        $name = method_exists($instance, 'getName') ? $instance->getName() : null;

        expect($name)->not->toBeEmpty("$class must define a command name.");
    }
});

it('can resolve package views referenced by service providers', function () {
    foreach ([
        'toastify::css',
        'toastify::js',
        'datatables::datatable',
        'datatables::filters.string',
        'datatables::partials.table',
        'datatables::pagination.default',
    ] as $view) {
        expect(View::exists($view))->toBeTrue("Expected view [$view] to exist.");
    }
});

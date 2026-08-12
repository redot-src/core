<?php

use Illuminate\Contracts\Console\Kernel;

it('registers template and params options on make:view', function () {
    $definition = $this->app->make(Kernel::class)
        ->all()['make:view']
        ->getDefinition();

    expect($definition->hasOption('template'))->toBeTrue()
        ->and($definition->hasOption('params'))->toBeTrue();
});

it('renders a dashboard stub when make:view is given a template and params', function () {
    $this->artisan('make:view', [
        'name' => 'dashboard/posts/index',
        '--template' => 'dashboard.index',
        '--params' => 'resource=posts&entity=post&datatable=posts',
        '--force' => true,
    ])->assertSuccessful();

    $path = resource_path('views/dashboard/posts/index.blade.php');

    expect($path)->toBeFile()
        ->and(file_get_contents($path))->toContain("route('dashboard.posts.create')");
});

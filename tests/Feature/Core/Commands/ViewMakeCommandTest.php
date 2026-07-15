<?php

use Illuminate\Support\Facades\File;

it('creates a view with the default framework stub', function () {
    $views = sys_get_temp_dir() . '/redot-view-make-' . uniqid();
    config()->set('view.paths', [$views]);

    try {
        $this->artisan('make:view', ['name' => 'reports.index'])
            ->assertSuccessful();

        expect($views . '/reports/index.blade.php')->toBeFile();
    } finally {
        File::deleteDirectory($views);
    }
});

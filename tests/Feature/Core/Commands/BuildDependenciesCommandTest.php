<?php

use Illuminate\Support\Facades\File;

it('builds the package inits and lets application inits override them', function () {
    $inits = public_path('assets/inits');
    File::ensureDirectoryExists($inits);
    File::put($inits . '/tomselect.js', "return 'application tomselect';");
    File::put($inits . '/custom.js', "return 'application custom';");

    try {
        $this->artisan('dependencies:build')->assertSuccessful();

        $build = File::get(dist_path('init.js'));

        expect($build)
            ->toContain('window.__inits["uploader"]')
            ->toContain("window.__inits[\"tomselect\"] = (() => { return 'application tomselect'; })();")
            ->toContain("window.__inits[\"custom\"] = (() => { return 'application custom'; })();")
            ->and(substr_count($build, 'window.__inits["tomselect"]'))->toBe(1);
    } finally {
        File::deleteDirectory($inits);
    }
});

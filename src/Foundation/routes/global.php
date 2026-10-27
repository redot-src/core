<?php

use Illuminate\Support\Facades\Route;
use Redot\Http\Controllers\HugeRteController;
use Redot\Http\Controllers\SelectController;
use Redot\Http\Controllers\UploaderController;

if (config('redot.components.hugerte.enabled')) {
    Route::post('hugerte/upload', [HugeRteController::class, 'store'])->middleware(config('redot.components.hugerte.middleware'))->name('hugerte.upload');
}

if (config('redot.components.select.enabled')) {
    Route::middleware(config('redot.components.select.middleware'))->group(function () {
        Route::get('tomselect/search', [SelectController::class, 'index'])->name('select.search');
        Route::get('tomselect/fetch', [SelectController::class, 'show'])->name('select.fetch');
    });
}

if (config('redot.components.uploader.enabled')) {
    Route::post('uploader/upload', [UploaderController::class, 'store'])->middleware(config('redot.components.uploader.middleware'))->name('uploader.upload');
}

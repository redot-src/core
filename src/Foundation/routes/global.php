<?php

use Illuminate\Support\Facades\Route;
use Redot\Http\Controllers\HugeRteController;
use Redot\Http\Controllers\SelectController;
use Redot\Http\Controllers\UploaderController;

Route::post('hugerte/upload', [HugeRteController::class, 'store'])->name('hugerte.upload');

Route::get('tomselect/search', [SelectController::class, 'index'])->name('select.search');
Route::get('tomselect/fetch', [SelectController::class, 'show'])->name('select.fetch');

Route::post('uploader/upload', [UploaderController::class, 'store'])->name('uploader.upload');

<?php

use Illuminate\Support\Facades\Route;

Route::get('custom', fn () => 'custom')->name('custom');

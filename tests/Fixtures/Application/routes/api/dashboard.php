<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => 'dashboard api')->name('index');

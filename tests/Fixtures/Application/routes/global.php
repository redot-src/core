<?php

use Illuminate\Support\Facades\Route;

Route::get('global', fn () => 'global')->name('index');

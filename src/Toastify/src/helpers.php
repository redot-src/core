<?php

use Illuminate\Support\Facades\App;
use Redot\Toastify\Toastify;

/**
 * Get the toastify instance.
 */
function toastify(): Toastify
{
    return App::make(Toastify::class);
}

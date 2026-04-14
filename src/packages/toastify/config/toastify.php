<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Toastify CDN links
    |--------------------------------------------------------------------------
    |
    | Here you may specify the CDN links for the toastify library.
    |
    */

    'cdn' => [
        'js' => '/vendor/toastify/toastify.min.js',
        'css' => '/vendor/toastify/toastify.min.css',
    ],

    /*
    |--------------------------------------------------------------------------
    | Toastify Toastifiers Options
    |--------------------------------------------------------------------------
    |
    | Here you may specify the toastifiers options for the toastify library.
    | Each toastifier will be available as a method in the Toastify facade.
    |
    */

    'toastifiers' => [
        'toast' => [
            'style' => [
                'color' => 'var(--tblr-body, #fff)',
                'background' => 'var(--tblr-body-bg, #182433)',
                'border' => '1px solid var(--tblr-border-color, #dee2e6)',
            ],
        ],
        'error' => [
            'style' => [
                'color' => 'var(--tblr-white, #fff)',
                'background' => 'var(--tblr-danger, #d63939)',
                'border' => '1px solid var(--tblr-danger, #dee2e6)',
            ],
        ],
        'success' => [
            'style' => [
                'color' => 'var(--tblr-white, #fff)',
                'background' => 'var(--tblr-success, #2fb344)',
                'border' => '1px solid var(--tblr-success, #dee2e6)',
            ],
        ],
        'info' => [
            'style' => [
                'color' => 'var(--tblr-white, #fff)',
                'background' => 'var(--tblr-info, #4299e1)',
                'border' => '1px solid var(--tblr-info, #dee2e6)',
            ],
        ],
        'warning' => [
            'style' => [
                'color' => 'var(--tblr-white, #fff)',
                'background' => 'var(--tblr-warning, #f76707)',
                'border' => '1px solid var(--tblr-warning, #dee2e6)',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Toastify Default Options
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default options for the toastify library.
    |
    */

    'defaults' => [
        'gravity' => 'toastify-bottom',
        'position' => 'right',
        'close' => true,
    ],
];

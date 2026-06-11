<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Toastifiers Options
    |--------------------------------------------------------------------------
    |
    | Here you may specify the toastifiers options for the toastify library.
    | Each toastifier will be available as a method in the Toastify facade.
    |
    */

    'toastifiers' => [
        'toast' => [
            'style' => [
                'color' => 'var(--tblr-body)',
                'background' => 'var(--tblr-body-bg)',
                'border' => '1px solid var(--tblr-border-color)',
            ],
        ],
        'error' => [
            'style' => [
                'color' => 'var(--tblr-white)',
                'background' => 'var(--tblr-danger)',
                'border' => '1px solid var(--tblr-danger)',
            ],
        ],
        'success' => [
            'style' => [
                'color' => 'var(--tblr-white)',
                'background' => 'var(--tblr-success)',
                'border' => '1px solid var(--tblr-success)',
            ],
        ],
        'info' => [
            'style' => [
                'color' => 'var(--tblr-white)',
                'background' => 'var(--tblr-info)',
                'border' => '1px solid var(--tblr-info)',
            ],
        ],
        'warning' => [
            'style' => [
                'color' => 'var(--tblr-white)',
                'background' => 'var(--tblr-warning)',
                'border' => '1px solid var(--tblr-warning)',
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
        'position' => 'bottom-right',
        'close' => true,
    ],
];

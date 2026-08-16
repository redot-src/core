<?php

return [

    /*--------------------------------------------------------------------------
    | Toastifiers
    |--------------------------------------------------------------------------
    |
    | The toastifiers that are available for the application.
    |
    */

    'toastifiers' => [
        'toast' => [
            'icon' => 'ti ti-bell',
            'color' => 'var(--tblr-secondary)',
        ],
        'error' => [
            'icon' => 'ti ti-circle-x',
            'color' => 'var(--tblr-danger)',
        ],
        'success' => [
            'icon' => 'ti ti-circle-check',
            'color' => 'var(--tblr-success)',
        ],
        'info' => [
            'icon' => 'ti ti-info-circle',
            'color' => 'var(--tblr-info)',
        ],
        'warning' => [
            'icon' => 'ti ti-alert-triangle',
            'color' => 'var(--tblr-warning)',
        ],
    ],

    /*--------------------------------------------------------------------------
    | Toastify Defaults
    |--------------------------------------------------------------------------
    |
    | The default values for the toastifiers.
    |
    */

    'defaults' => [
        'position' => 'bottom-right',
        'close' => true,
        'autohide' => true,
        'delay' => 5000,
    ],
];

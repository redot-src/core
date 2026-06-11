<?php

return [

    'toastifiers' => [
        'toast' => [
            'icon' => 'fas fa-bell',
            'color' => 'var(--tblr-secondary)',
        ],
        'error' => [
            'icon' => 'fas fa-circle-xmark',
            'color' => 'var(--tblr-danger)',
        ],
        'success' => [
            'icon' => 'fas fa-circle-check',
            'color' => 'var(--tblr-success)',
        ],
        'info' => [
            'icon' => 'fas fa-circle-info',
            'color' => 'var(--tblr-info)',
        ],
        'warning' => [
            'icon' => 'fas fa-triangle-exclamation',
            'color' => 'var(--tblr-warning)',
        ],
    ],

    'defaults' => [
        'position' => 'bottom-right',
        'close' => true,
        'autohide' => true,
        'delay' => 5000,
    ],
];

<?php

use Redot\Datatables\Adapters\PDF\LaravelMpdf;

return [

    /*
    |--------------------------------------------------------------------------
    | Redot Datatables config
    |--------------------------------------------------------------------------
    |
    | Here you can specify the configuration of the redot datatable.
    |
    */

    'assets' => [
        'css' => [
            'file' => dirname(__DIR__) . '/resources/css/datatables.css',
            'uri' => 'datatables/datatables.css',
            'route' => 'datatables.css',
        ],
        'js' => [
            'file' => dirname(__DIR__) . '/resources/js/datatables.js',
            'uri' => 'datatables/datatables.js',
            'route' => 'datatables.js',
        ],
    ],

    'export' => [
        'xlsx' => [
            'enabled' => true,
        ],

        'csv' => [
            'enabled' => true,
        ],

        'json' => [
            'enabled' => true,
        ],

        'pdf' => [
            'enabled' => true,
            'template' => 'datatables::pdf.default',
            'adapter' => LaravelMpdf::class,
            'options' => [
                'format' => 'A4',
                'orientation' => 'P',
            ],
        ],
    ],
];

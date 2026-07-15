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
        'css' => 'vendor/datatables/datatables.css',
        'js' => 'vendor/datatables/datatables.js',
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

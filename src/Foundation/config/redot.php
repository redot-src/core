<?php

use Redot\Datatables\Adapters\PDF\LaravelMpdf;
use Redot\Datatables\Exporters\CsvExporter;
use Redot\Datatables\Exporters\JsonExporter;
use Redot\Datatables\Exporters\PdfExporter;
use Redot\Datatables\Exporters\XlsxExporter;

return [

    /*--------------------------------------------------------------------------
    | Redot Features
    |--------------------------------------------------------------------------
    |
    | The features that are enabled for the application, You can enable or
    | disable features as per your requirements.
    |
    */

    // Disable only for tests that exercise feature configuration, before routes load.
    'testing' => [
        'enable_all_features' => true,
    ],

    'features' => [
        'website-api' => [
            'enabled' => true,
        ],

        'dashboard-api' => [
            'enabled' => true,
            'prefix' => 'dashboard',
        ],

        'website' => [
            'enabled' => true,
        ],

        'dashboard' => [
            'enabled' => true,
            'prefix' => 'dashboard',
            'guard' => 'admins',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Locales
    |--------------------------------------------------------------------------
    |
    | The list of available locales for the website and dashboard.
    |
    */

    'locales' => [
        [
            'code' => 'en',
            'name' => 'English',
            'is_rtl' => false,
        ],

        [
            'code' => 'ar',
            'name' => 'العربية',
            'is_rtl' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------------
    |
    | Route-level behavior that affects URL generation and fallback redirects.
    |
    */

    'routing' => [
        'append_locale_to_url' => true,
        'redirect_non_locale_urls' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    |
    | Application files Redot loads by convention. Route files and the layouts
    | path are relative to the application base path. When overriding, copy
    | the whole block so no path is left undefined.
    |
    */

    'paths' => [
        'routes' => [
            'global' => 'routes/global.php',
            'website' => 'routes/website.php',
            'dashboard' => 'routes/dashboard.php',
            'website-api' => 'routes/api/website.php',
            'dashboard-api' => 'routes/api/dashboard.php',
        ],

        'layouts' => [
            'path' => 'resources/layouts',
            'namespace' => 'App\\View\\Layouts',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Components
    |--------------------------------------------------------------------------
    |
    | The endpoints behind <x-select-remote>, <x-uploader>, and the image
    | uploads of <x-rich-editor>. Disable the ones the application doesn't
    | use, and add middleware (such as auth) to limit who can call them. The
    | rich editor still works without its endpoint, minus image uploads.
    |
    */

    'components' => [
        'select' => [
            'enabled' => true,
            'middleware' => ['throttle:120,1'],
        ],

        'uploader' => [
            'enabled' => true,
            'middleware' => ['throttle:120,1'],
        ],

        'hugerte' => [
            'enabled' => true,
            'middleware' => ['throttle:120,1'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    |
    | Dashboard route gating is driven by Spatie permissions resolved from
    | route names. Set bypass to true while developing locally so every Gate
    | check passes without syncing or assigning permissions. Bypass is never
    | honored outside the local environment.
    |
    */

    'permissions' => [
        'bypass' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Uploads
    |--------------------------------------------------------------------------
    |
    | File extensions that may be stored in the public uploads directory. The
    | extension is detected from the file contents, not the client filename.
    |
    */

    'uploads' => [
        'allowed_extensions' => [
            '7z', 'aac', 'avi', 'avif', 'bmp', 'csv', 'doc', 'docx', 'flac', 'gif',
            'gz', 'jpeg', 'jpg', 'm4a', 'mkv', 'mov', 'mp3', 'mp4', 'mpeg', 'odp',
            'ods', 'odt', 'oga', 'ogg', 'pdf', 'png', 'ppt', 'pptx', 'rar', 'tar',
            'tif', 'tiff', 'txt', 'wav', 'webm', 'webp', 'xls', 'xlsx', 'zip',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Datatables
    |--------------------------------------------------------------------------
    |
    | Assets and export formats for Livewire datatables. Each export format
    | can be disabled or given its own exporter class.
    |
    */

    'datatables' => [
        'assets' => [
            'css' => 'vendor/redot/datatables/datatables.css',
            'js' => 'vendor/redot/datatables/datatables.js',
        ],

        'export' => [
            'xlsx' => [
                'enabled' => true,
                'exporter' => XlsxExporter::class,
            ],

            'csv' => [
                'enabled' => true,
                'exporter' => CsvExporter::class,
            ],

            'json' => [
                'enabled' => true,
                'exporter' => JsonExporter::class,
            ],

            'pdf' => [
                'enabled' => true,
                'exporter' => PdfExporter::class,
                'template' => 'redot::datatables.pdf.default',
                'adapter' => LaravelMpdf::class,
                'options' => [
                    'format' => 'A4',
                    'orientation' => 'P',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Toastify
    |--------------------------------------------------------------------------
    |
    | The toast types available to toastify() and their default display
    | options.
    |
    */

    'toastify' => [
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

        // The default values for the toastifiers.

        'defaults' => [
            'position' => 'bottom-right',
            'close' => true,
            'autohide' => true,
            'delay' => 5000,
        ],
    ],

];

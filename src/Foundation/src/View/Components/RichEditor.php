<?php

namespace Redot\View\Components;

use Illuminate\Support\Facades\Route;
use Illuminate\View\ComponentAttributeBag;

class RichEditor extends Textarea
{
    /**
     * View path for the component.
     */
    public string $view = 'components.rich-editor';

    /**
     * Prepare the attributes and any extra view data once attributes are available.
     */
    protected function prepare(array $data): array
    {
        $this->attributes(function (ComponentAttributeBag $attributes) {
            return $attributes->merge([
                'init' => 'hugerte',
                // Image uploads are offered only while the package endpoints are enabled.
                'hugerte-upload-url' => Route::has('global.hugerte.upload') ? route('global.hugerte.upload') : null,
            ]);
        });

        return [];
    }
}

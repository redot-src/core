<?php

namespace Redot\Datatables\Adapters\PDF;

use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Collection;

class DomPdf extends Adapter
{
    /**
     * Generate the PDF instance.
     */
    protected function generate(string $template, array $headings, Collection $rows, array $options = []): object
    {
        // Set options.
        PDF::setOptions($options);

        return PDF::loadView($template, compact('headings', 'rows'), []);
    }

    /**
     * Check if the adapter is supported.
     */
    public function supported(): bool
    {
        return class_exists(PDF::class);
    }
}

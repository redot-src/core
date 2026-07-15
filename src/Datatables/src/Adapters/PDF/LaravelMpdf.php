<?php

namespace Redot\Datatables\Adapters\PDF;

use Illuminate\Support\Collection;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf as PDF;

class LaravelMpdf extends Adapter
{
    /**
     * Generate the PDF instance.
     */
    protected function generate(string $template, array $headings, Collection $rows, array $options = []): object
    {
        return PDF::chunkLoadView('<!-- chunk -->', $template, compact('headings', 'rows'), [], $options);
    }

    /**
     * Check if the adapter is supported.
     */
    public function supported(): bool
    {
        return class_exists(PDF::class);
    }
}

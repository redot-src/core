<?php

namespace Redot\Datatables\Adapters\PDF;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class Adapter
{
    /**
     * Download the PDF file.
     */
    public function download(string $template, array $headings, Collection $rows, array $options = []): StreamedResponse|Response
    {
        $filename = sprintf('export-%s.pdf', now()->format('Y-m-d_H-i-s'));

        // Generate the PDF file.
        $pdf = $this->generate($template, $headings, $rows, $options);

        return response()->stream(function () use ($pdf) {
            echo $pdf->output();
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    /**
     * Generate the PDF instance.
     */
    abstract protected function generate(string $template, array $headings, Collection $rows, array $options = []): object;

    /**
     * Check if the adapter is supported.
     */
    public function supported(): bool
    {
        return true;
    }
}

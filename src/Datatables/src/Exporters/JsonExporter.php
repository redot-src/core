<?php

namespace Redot\Datatables\Exporters;

use Illuminate\Support\Collection;
use Illuminate\Support\Js;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JsonExporter implements Exporter
{
    /**
     * Determine if the exporter's dependencies are available.
     */
    public function supported(): bool
    {
        return true;
    }

    /**
     * Throw when the exporter's dependencies are not available.
     */
    public function ensureSupported(): void
    {
        //
    }

    /**
     * Determine if the exporter consumes raw column values.
     */
    public function raw(): bool
    {
        return true;
    }

    /**
     * Download the export file for the given headings and rows.
     */
    public function download(array $headings, Collection $rows): StreamedResponse
    {
        $items = $rows->map(fn ($row) => array_combine($headings, $row))->toArray();
        $filename = sprintf('export-%s.json', now()->format('Y-m-d_H-i-s'));
        $flags = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;

        $headers = [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->streamDownload(fn () => print Js::encode($items, $flags), $filename, $headers);
    }
}

<?php

namespace Redot\Datatables\Exporters;

use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface Exporter
{
    /**
     * Determine if the exporter's dependencies are available.
     */
    public function supported(): bool;

    /**
     * Throw when the exporter's dependencies are not available.
     */
    public function ensureSupported(): void;

    /**
     * Determine if the exporter consumes raw column values.
     */
    public function raw(): bool;

    /**
     * Download the export file for the given headings and rows.
     */
    public function download(array $headings, Collection $rows): BinaryFileResponse|StreamedResponse|Response;
}

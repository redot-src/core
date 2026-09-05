<?php

namespace Redot\Datatables\Exporters;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Redot\Datatables\Exceptions\MissingDependencyException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

abstract class ExcelExporter implements Exporter
{
    /**
     * The Excel writer format of the export.
     */
    protected string $format;

    /**
     * Determine if the exporter's dependencies are available.
     */
    public function supported(): bool
    {
        return class_exists('Maatwebsite\Excel\Excel');
    }

    /**
     * Determine if the exporter consumes raw column values.
     */
    public function raw(): bool
    {
        return true;
    }

    /**
     * Throw when the exporter's dependencies are not available.
     */
    public function ensureSupported(): void
    {
        if (! $this->supported()) {
            throw new MissingDependencyException(sprintf('Please install the "maatwebsite/excel" package to export %s files.', $this->format));
        }
    }

    /**
     * Download the export file for the given headings and rows.
     */
    public function download(array $headings, Collection $rows): BinaryFileResponse
    {
        $this->ensureSupported();

        $filename = sprintf('export-%s.%s', now()->format('Y-m-d_H-i-s'), $this->format);

        $path = sprintf('exports/%s.%s', Str::uuid(), $this->format);

        // Store on the local disk explicitly, the default disk may not have a local root.
        $rows->prepend($headings)->storeExcel($path, 'local', ucfirst($this->format));

        return response()->download(Storage::disk('local')->path($path), $filename)->deleteFileAfterSend(true);
    }
}

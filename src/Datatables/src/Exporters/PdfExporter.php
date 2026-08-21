<?php

namespace Redot\Datatables\Exporters;

use Illuminate\Support\Collection;
use Redot\Datatables\Adapters\PDF\Adapter;
use Redot\Datatables\Exceptions\MissingDependencyException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfExporter implements Exporter
{
    /**
     * Create a new PDF exporter instance.
     */
    public function __construct(
        protected string $template,
        protected string $adapter,
        protected array $options = [],
    ) {}

    /**
     * Determine if the exporter's dependencies are available.
     */
    public function supported(): bool
    {
        $adapter = new $this->adapter;

        return $adapter instanceof Adapter && $adapter->supported();
    }

    /**
     * Throw when the exporter's dependencies are not available.
     */
    public function ensureSupported(): void
    {
        if (! $this->supported()) {
            throw new MissingDependencyException(sprintf('The PDF adapter "%s" is not supported.', $this->adapter));
        }
    }

    /**
     * Determine if the exporter consumes raw column values.
     */
    public function raw(): bool
    {
        return false;
    }

    /**
     * Download the export file for the given headings and rows.
     */
    public function download(array $headings, Collection $rows): StreamedResponse|Response
    {
        $this->ensureSupported();

        return (new $this->adapter)->download($this->template, $headings, $rows, $this->options);
    }
}

<?php

namespace Redot\Datatables\Exporters;

use InvalidArgumentException;

class ExportManager
{
    /**
     * The default exporter for each format, used when the config does not name one.
     *
     * @var array<string, class-string<Exporter>>
     */
    protected const DEFAULT_EXPORTERS = [
        'xlsx' => XlsxExporter::class,
        'csv' => CsvExporter::class,
        'json' => JsonExporter::class,
        'pdf' => PdfExporter::class,
    ];

    /**
     * Create a new export manager instance.
     */
    public function __construct(
        protected bool $exportable,
        protected array $allowed,
    ) {}

    /**
     * Ensure the requested export format is available.
     */
    public function ensureAllowed(string $format): void
    {
        abort_unless($this->exportable && in_array($format, $this->allowed, true), 403);
    }

    /**
     * Resolve the exporter for the given format.
     */
    public function exporter(string $format, array $parameters = []): Exporter
    {
        $exporter = config("datatables.export.$format.exporter") ?? static::DEFAULT_EXPORTERS[$format] ?? null;

        if (! $exporter || ! is_a($exporter, Exporter::class, true)) {
            throw new InvalidArgumentException(sprintf('No exporter registered for the "%s" format.', $format));
        }

        return app()->makeWith($exporter, $parameters);
    }
}

<?php

namespace Redot\Datatables\Exporters;

class CsvExporter extends ExcelExporter
{
    /**
     * The Excel writer format of the export.
     */
    protected string $format = 'csv';
}

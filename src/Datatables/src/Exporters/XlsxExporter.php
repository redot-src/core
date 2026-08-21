<?php

namespace Redot\Datatables\Exporters;

class XlsxExporter extends ExcelExporter
{
    /**
     * The Excel writer format of the export.
     */
    protected string $format = 'xlsx';
}

<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Redot\Datatables\Exporters\ExcelExporter;

it('keeps simultaneous exports separate while preserving the download name', function (string $format) {
    $this->freezeTime();
    Storage::fake('local');
    $exporter = new class($format) extends ExcelExporter
    {
        public function __construct(string $format)
        {
            $this->format = $format;
        }

        public function supported(): bool
        {
            return true;
        }
    };

    // Stand in for the optional Excel package at its file-writing boundary.
    Collection::macro('storeExcel', function ($path, $disk, $writer) {
        Storage::disk($disk)->put($path, $this->toJson());
    });

    try {
        $first = $exporter->download(['Name'], collect([['First admin']]));
        $second = $exporter->download(['Name'], collect([['Second admin']]));

        expect($first->getFile()->getPathname())->not->toBe($second->getFile()->getPathname())
            ->and(file_get_contents($first->getFile()->getPathname()))->toContain('First admin')->not->toContain('Second admin')
            ->and(file_get_contents($second->getFile()->getPathname()))->toContain('Second admin')
            ->and($first->headers->get('Content-Disposition'))->toBe($second->headers->get('Content-Disposition'));

        ob_start();
        $first->sendContent();
        ob_end_clean();

        expect(file_exists($first->getFile()->getPathname()))->toBeFalse()
            ->and(file_exists($second->getFile()->getPathname()))->toBeTrue();
    } finally {
        // Remove only the temporary macro, preserving other registered macros.
        $property = new ReflectionProperty(Collection::class, 'macros');
        $macros = $property->getValue();
        unset($macros['storeExcel']);
        $property->setValue(null, $macros);
    }
})->with(['csv', 'xlsx']);

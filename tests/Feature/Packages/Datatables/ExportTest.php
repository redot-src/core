<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Redot\Datatables\Columns\Column;
use Redot\Datatables\Columns\TextColumn;
use Tests\Fixtures\Datatables\RunActionDatatable;
use Tests\Fixtures\Datatables\RunActionPost;

beforeEach(function () {
    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->boolean('approved')->default(false);
        $table->string('title')->nullable();
        $table->timestamps();
    });
});

it('exports actual values for XLSX, CSV, and JSON without encoding or stripping them', function () {
    RunActionPost::query()->create(['title' => 'Tom & Jerry <Co>']);

    $datatable = new class extends RunActionDatatable
    {
        public function columns(): array
        {
            return [Column::make('title', 'Title')];
        }

        public function exportData(): array
        {
            return $this->getExportData(raw: true);
        }
    };

    [$headings, $rows] = $datatable->exportData();

    expect($headings)->toBe(['Title'])
        ->and($rows->first())->toBe(['Tom & Jerry <Co>']);
});

it('exports email text without the mailto anchor', function () {
    RunActionPost::query()->create(['title' => 'taylor@example.com']);

    $datatable = new class extends RunActionDatatable
    {
        public function columns(): array
        {
            return [TextColumn::make('title', 'Email')->email()];
        }

        public function exportData(): array
        {
            return $this->getExportData(raw: true);
        }
    };

    [$headings, $rows] = $datatable->exportData();

    expect($headings)->toBe(['Email'])
        ->and($rows->first())->toBe(['taylor@example.com']);
});

it('rejects export calls when exporting is disabled', function (string $method) {
    Livewire::test(RunActionDatatable::class, [
        'exportable' => false,
        'allowedExports' => ['xlsx', 'csv', 'json', 'pdf'],
    ])
        ->call($method)
        ->assertForbidden();
})->with([
    'XLSX' => 'toXlsx',
    'CSV' => 'toCsv',
    'JSON' => 'toJson',
    'PDF' => 'toPdf',
]);

it('rejects export formats outside the datatable allowlist', function (string $method) {
    Livewire::test(RunActionDatatable::class, [
        'exportable' => true,
        'allowedExports' => [],
    ])
        ->call($method)
        ->assertForbidden();
})->with([
    'XLSX' => 'toXlsx',
    'CSV' => 'toCsv',
    'JSON' => 'toJson',
    'PDF' => 'toPdf',
]);

it('allows configured export formats', function () {
    Livewire::test(RunActionDatatable::class, [
        'exportable' => true,
        'allowedExports' => ['json'],
    ])
        ->call('toJson')
        ->assertFileDownloaded();
});

it('prevents the client from mutating export settings', function (string $property, mixed $value) {
    $component = Livewire::test(RunActionDatatable::class);

    expect(fn () => $component->set($property, $value))
        ->toThrow(CannotUpdateLockedPropertyException::class);
})->with([
    'exportable flag' => ['exportable', true],
    'allowed formats' => ['allowedExports', ['xlsx', 'csv', 'json', 'pdf']],
    'pdf adapter class' => ['pdfAdapter', 'App\\Evil'],
]);

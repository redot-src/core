<?php

it('registers the datatable views and translations under the redot namespace', function () {
    app()->setLocale('ar');

    expect(view()->exists('redot::datatables.datatable'))->toBeTrue()
        ->and(view()->exists('redot::datatables.partials.table'))->toBeTrue()
        ->and(__('redot::datatables.empty'))->not->toBe('redot::datatables.empty')
        ->and(__('redot::datatables.empty'))->not->toBe(trans('redot::datatables.empty', locale: 'en'));
});

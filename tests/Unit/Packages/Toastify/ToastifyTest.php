<?php

use Redot\Toastify\Toastify;

it('stores toast messages in the session', function () {
    toastify()->success('Saved', ['duration' => 1000]);
    toastify()->error('Failed');

    expect(session('toastify'))->toBe([
        ['message' => 'Saved', 'type' => 'success', 'options' => ['duration' => 1000]],
        ['message' => 'Failed', 'type' => 'error', 'options' => []],
    ]);
});

it('renders toastify css and js views', function () {
    $toastify = app(Toastify::class);

    expect($toastify->css())->toContain('toastify')
        ->and($toastify->js())->toContain('toastify');
});

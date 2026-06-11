<?php

it('stores toast messages in the session in the order they are pushed', function () {
    toastify()->success('Saved', options: ['duration' => 1000]);
    toastify()->error('Failed');

    expect(session('toastify'))->toBe([
        ['title' => 'Saved', 'message' => null, 'type' => 'success', 'options' => ['duration' => 1000]],
        ['title' => 'Failed', 'message' => null, 'type' => 'error', 'options' => []],
    ]);
});

it('uses the magic method name as the toast type for any unknown level', function () {
    toastify()->info('Heads up');

    expect(session('toastify'))->toBe([
        ['title' => 'Heads up', 'message' => null, 'type' => 'info', 'options' => []],
    ]);
});

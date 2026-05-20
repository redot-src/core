<?php

it('stores toast messages in the session in the order they are pushed', function () {
    toastify()->success('Saved', ['duration' => 1000]);
    toastify()->error('Failed');

    expect(session('toastify'))->toBe([
        ['message' => 'Saved', 'type' => 'success', 'options' => ['duration' => 1000]],
        ['message' => 'Failed', 'type' => 'error', 'options' => []],
    ]);
});

it('uses the magic method name as the toast type for any unknown level', function () {
    toastify()->info('Heads up');

    expect(session('toastify'))->toBe([
        ['message' => 'Heads up', 'type' => 'info', 'options' => []],
    ]);
});

<?php

use Redot\Rules\Phone;

it('accepts valid phone numbers for the configured country', function () {
    expect((new Phone('EG'))->passes('phone', '01001234567'))->toBeTrue()
        ->and((new Phone('US'))->passes('phone', '+14155552671'))->toBeTrue();
});

it('rejects invalid phone numbers', function () {
    expect((new Phone('EG'))->passes('phone', '123'))->toBeFalse()
        ->and((new Phone('US'))->passes('phone', 'not-a-phone'))->toBeFalse();
});

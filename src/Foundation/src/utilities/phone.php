<?php

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * Format the given phone number.
 */
function format_phone(string $phone, string $country = 'EG'): string
{
    $instance = PhoneNumberUtil::getInstance();

    return $instance->format($instance->parse($phone, $country), PhoneNumberFormat::E164);
}

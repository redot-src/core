<?php

/**
 * Check if the request from a mobile device.
 */
function is_mobile(): bool
{
    if (! app()->bound('request')) {
        return false;
    }

    $userAgent = request()->userAgent();

    if (empty($userAgent)) {
        return false;
    }

    return (bool) preg_match(
        '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',
        $userAgent
    );
}

/**
 * Check if the request from a desktop device.
 */
function is_desktop(): bool
{
    return ! is_mobile();
}

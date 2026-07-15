<?php

foreach (glob(__DIR__ . '/utilities/*.php') as $file) {
    require_once $file;
}

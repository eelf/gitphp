<?php

require_once __DIR__ . '/vendor/autoload.php';

set_error_handler([\lib\StatSlow::class, 'error']);

\lib\Context::setConfig(require __DIR__ . '/config.php');


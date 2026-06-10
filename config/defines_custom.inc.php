<?php

define('_KPY_LIMIT_SHIPPING_FREE_', 39.9);

define('_PS_LOG_DIR_', _PS_ROOT_DIR_ . '/log/');

if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}
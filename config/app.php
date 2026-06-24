<?php

define('APP_NAME', 'Seleno Lelang');
define('BASE_URL', '/SPK_lelang');

define('MAIL_FROM_EMAIL', 'no-reply@seleno-lelang.local');
define('MAIL_FROM_NAME', 'Seleno Lelang');

define('PRICE_MIN', 10000);
define('PRICE_MAX', 999999999999);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

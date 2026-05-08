<?php

// Absolute path to application root
define('APP_ROOT', __DIR__);

// Optional helpers
define('BIN_PATH', APP_ROOT . '/bin');
define('MODEL_PATH', APP_ROOT . '/bin/Model');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>

<?php
/**
 * Bootstraps the application on every request.
 * Loaded once by index.php.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

// --- session hardening -----------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly',  '1');
    ini_set('session.use_strict_mode',  '1');
    session_name('RHU_MAKILALA_SESS');
    session_start();
}

// --- security headers -------------------------------------------------
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-XSS-Protection: 1; mode=block');

// --- autoload helpers / middlewares / models / controllers ------------
spl_autoload_register(function ($class) {
    $dirs = ['helpers', 'middlewares', 'models', 'controllers'];
    foreach ($dirs as $d) {
        $file = BASE_PATH . $d . DIRECTORY_SEPARATOR . $class . '.php';
        if (file_exists($file)) { require_once $file; return; }
    }
});

// Always load helpers file (procedural functions)
require_once BASE_PATH . 'helpers/functions.php';

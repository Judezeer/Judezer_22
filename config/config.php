<?php
/**
 * Global application configuration.
 * All constants live here so the rest of the code stays clean.
 */

// -------------------- Environment --------------------
define('APP_NAME',   'RHU Makilala HMIS');
define('APP_ENV',    'production'); // 'development' shows errors
define('APP_DEBUG',  false);

// -------------------- Paths / URLs -------------------
// BASE_URL is auto-detected so the app runs at http://localhost/rhu-makilala
// regardless of the folder name it is dropped into.
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$scriptDir = rtrim($scriptDir, '/');
// If the app sits at web root, $scriptDir === ''
define('BASE_URL',   ($scriptDir === '' ? '/' : $scriptDir . '/'));
define('BASE_PATH',  realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR);
define('VIEW_PATH',  BASE_PATH . 'views' . DIRECTORY_SEPARATOR);
define('UPLOAD_PATH', BASE_PATH . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);
define('UPLOAD_URL',  BASE_URL . 'assets/uploads/');
define('ASSET_URL',   BASE_URL . 'assets/');

// -------------------- Security -----------------------
define('SESSION_TIMEOUT', 1800); // 30 minutes idle
define('CSRF_TOKEN_NAME', '_csrf');

// -------------------- Error reporting ---------------
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// -------------------- Timezone -----------------------
date_default_timezone_set('Asia/Manila');

<?php
/**
 * Front controller / router.
 * URL pattern: index.php?url=controller/action/param1/param2
 */
require_once __DIR__ . '/config/bootstrap.php';

// Default route by role
$defaultByRole = [
    'admin'      => 'admin/dashboard',
    'nurse'      => 'nurse/dashboard',
    'pharmacist' => 'pharmacist/dashboard',
    'patient'    => 'patient/dashboard',
];

$url = trim($_GET['url'] ?? '', '/');
if ($url === '') {
    if (is_logged_in()) {
        redirect('index.php?url=' . $defaultByRole[$_SESSION['user']['role']]);
    }
    redirect('index.php?url=auth/login');
}

$parts    = explode('/', $url);
$section  = $parts[0] ?? '';       // e.g. admin, nurse, pharmacist, patient, auth, api
$action   = $parts[1] ?? 'index';  // e.g. dashboard, patients, login
$params   = array_slice($parts, 2);

// Map URL "section" to a Controller class
$controllerMap = [
    'auth'       => 'AuthController',
    'admin'      => 'AdminController',
    'nurse'      => 'NurseController',
    'pharmacist' => 'PharmacistController',
    'patient'    => 'PatientController',
    'api'        => 'ApiController',
];

if (!isset($controllerMap[$section])) {
    http_response_code(404);
    die('Page not found.');
}

$controllerClass = $controllerMap[$section];
if (!class_exists($controllerClass)) {
    http_response_code(500);
    die('Controller missing: ' . e($controllerClass));
}

$controller = new $controllerClass();
$method = preg_replace('/[^a-zA-Z0-9_]/', '', $action);
if (!method_exists($controller, $method)) {
    http_response_code(404);
    die('Action not found: ' . e($method));
}

// Any POST action must pass CSRF verification (except login form,
// which handles it internally so we can show a nicer error).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !($section === 'auth' && $method === 'login')) {
    AuthMiddleware::verifyCsrf();
}

call_user_func_array([$controller, $method], $params);

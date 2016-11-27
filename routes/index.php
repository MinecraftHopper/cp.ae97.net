<?php

define('BASE_DIR', dirname(__DIR__) . '/');
define('ASSET_DIR', BASE_DIR . '/assets/');
define('CONFIG_DIR', BASE_DIR . '/config/');
define('HTML_DIR', BASE_DIR . '/html/');
define('ROUTES_DIR', BASE_DIR . '/routes/');
define('LOADER_DIR', BASE_DIR . '/functions/');

require_once BASE_DIR . 'vendor/autoload.php';
require_once BASE_DIR . 'library/autoload.php';
require_once LOADER_DIR . 'autoload.php';

use \AE97\Panel\Utilities;

session_start();

$klein = new \Klein\Klein();

$klein->with('/', ROUTES_DIR . 'root/index.php');
$klein->with('/auth', ROUTES_DIR . 'auth/index.php');
$klein->with('/admin', ROUTES_DIR . 'admin/index.php');
$klein->with('/factoid', ROUTES_DIR . 'factoid/index.php');
$klein->with('/pmc', ROUTES_DIR . 'pmc/index.php');
$klein->with('/hjt', ROUTES_DIR . 'hjt/index.php');

$klein->respond('GET', '/error/[i:code]', function($request, $response, $service) {
    $service->render(HTML_DIR . 'index.phtml', array('page' => HTML_DIR . 'errors/' . $request->param('code') . '.phtml'));
});

$klein->onHttpError(function($httpCode, $klein) {
    $klein->service()->render(HTML_DIR . 'index.phtml', array('action' => $httpCode, 'try' => $klein->request()->uri(), 'page' => HTML_DIR . 'errors/' . $httpCode . '.phtml'));
});

$klein->onError(function($klein, $err_msg) {
    Utilities::logError($err_msg);
    $klein->service()->flash("Error: " . $err_msg);
    $klein->service()->back();
});

$klein->dispatch();

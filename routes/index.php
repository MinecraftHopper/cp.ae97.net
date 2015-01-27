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

$klein->respond('GET', '/[|index|index.php:page]?', function($request, $response, $service) {
    $service->render(HTML_DIR . 'index.phtml', array('action' => null, 'page' => null));
});

$klein->respond('GET', '/settings', function($request, $response, $service) {
    if (Authentication::verifySession()) {
        $service->render(HTML_DIR . 'index.phtml', array('action' => 'settings', 'page' => HTML_DIR . 'cp/user/settings.phtml'));
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$klein->with('/auth', ROUTES_DIR . 'auth/index.php');
$klein->with('/admin', ROUTES_DIR . 'admin/index.php');
$klein->with('/factoid', ROUTES_DIR . 'factoid/index.php');

$klein->onHttpError(function($httpCode, $klein) {
    $klein->service()->render(HTML_DIR . 'index.phtml', array('action' => '404', 'try' => $klein->request()->uri(), 'page' => HTML_DIR . 'errors/404.phtml'));
});

$klein->onError(function($klein, $err_msg) {
    Utilities::logError($err_msg);
    $klein->service()->flash("Error: " . $err_msg);
    $klein->service()->back();
});

$klein->dispatch();

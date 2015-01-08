<?php

define('BASE_DIR', dirname(__DIR__) . '/');
define('ASSET_DIR', dirname(__DIR__) . '/assets/');
define('CONFIG_DIR', dirname(__DIR__) . '/config/');
define('HTML_DIR', dirname(__DIR__) . '/html/');
define('ROUTES_DIR', dirname(__DIR__) . '/routes/');

require_once BASE_DIR . 'vendor/autoload.php';
require_once ASSET_DIR . 'php/functions.php';
require_once CONFIG_DIR . 'config.php';

session_start();

$klein = new \Klein\Klein();

$klein->respond(function($request, $response, $service, $app) {
    $app->register('factoid_db', function() {
        $_DATABASE = getDatabaseConfig();
        $db = new PDO("mysql:host=" . $_DATABASE['host'] . ";dbname=" . $_DATABASE['factoiddb'], $_DATABASE['user'], $_DATABASE['pass'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    });

    $app->register('auth_db', function() {
        $_DATABASE = getDatabaseConfig();
        $db = new PDO("mysql:host=" . $_DATABASE['host'] . ";dbname=" . $_DATABASE['authdb'], $_DATABASE['user'], $_DATABASE['pass']);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    });

    $app->register('mail', function() {
        $mail = new Mailgun\Mailgun(getMailgunConfig()['key']);
        return $mail;
    });
    $app->register('email', function() {
        return getMailgunConfig()['email'];
    });
    $app->register('domain', function() {
        return getSiteConfig()['domain'];
    });
    $app->register('site', function() {
        return getSiteConfig()['site'];
    });
    $app->register('fullsite', function() {
        return getSiteConfig()['fullsite'];
    });
});

$klein->respond('GET', '/[|index|index.php:page]?', function($request, $response, $service) {
    $service->render(HTML_DIR . 'index.phtml', array('action' => null, 'page' => null));
});

$klein->with('/auth', ROUTES_DIR . 'auth/index.php');
$klein->with('/cp', ROUTES_DIR . 'cp/index.php');
$klein->with('/factoid', ROUTES_DIR . 'factoid/index.php');

$klein->respond('404', function($request, $response, $service) {
    $service->render(HTML_DIR . 'index.phtml', array('action' => '404', 'try' => $request->uri(), 'page' => HTML_DIR . 'errors/404.phtml'));
});

$klein->onError(function($klein, $err_msg) {
    logError($err_msg);
    $klein->service()->flash("Error: " . $err_msg);
    $klein->service()->back();
});

$klein->dispatch();

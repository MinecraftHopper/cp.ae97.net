<?php

require_once __DIR__ . '/../assets/php/vendor/autoload.php';
require_once __DIR__ . '/../assets/php/functions.php';
require_once __DIR__ . '/../config/config.php';

session_start();

$klein = new \Klein\Klein();

$klein->respond(function($request, $response, $service, $app) {
    $app->register('factoid_db', function() {
        $_DATABASE = getDatabaseConfig();
        $db = new PDO("mysql:host=" . $_DATABASE['host'] . ";dbname=" . $_DATABASE['factoiddb'], $_DATABASE['user'], $_DATABASE['pass']);
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

$klein->respond('/ban', function($request, $response, $service, $app) {
    $response->redirect("/cp/admin/ban", 302);
});

$klein->respond('/user', function($request, $response, $service, $app) {
    $response->redirect("/cp/admin/user", 302);
});

$klein->respond('/bot', function($request, $response, $service, $app) {
    $response->redirect("/cp/admin/bot", 302);
});

$klein->respond('/settings', function($request, $response, $service, $app) {
    $response->redirect("/cp/settings", 302);
});

$klein->respond('GET', '/[|index|index.php:page]?', function($request, $response, $service, $app) {
    $service->render('index.phtml', array('action' => null, 'page' => null));
});

$klein->with('/auth', __DIR__ . '/auth/index.php');
$klein->with('/cp', __DIR__ . '/cp/index.php');
$klein->with('/factoid', __DIR__ . '/factoid/index.php');

$klein->respond('404', function($request, $response, $service, $app) {
    $service->render('index.phtml', array('action' => '404', 'try' => $request->uri(), 'page' => __DIR__ . '/../error/404.phtml'));
});

$klein->onError(function($klein, $err_msg) {
    $klein->service()->flash("Error: " . $err_msg);
    $klein->service()->back();
});

$klein->dispatch();

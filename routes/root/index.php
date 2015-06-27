<?php

use \AE97\Panel\Authentication,
    \AE97\Panel\User;

$this->respond('GET', '[|index|index.php:page]?', function($request, $response, $service) {
    $service->render(HTML_DIR . 'index.phtml', array('action' => null, 'page' => null));
});

$this->respond('GET', 'settings', function($request, $response, $service) {
    if (Authentication::verifySession()) {
        $service->render(HTML_DIR . 'index.phtml', array('action' => 'settings', 'page' => HTML_DIR . 'cp/user/settings.phtml', 'nickserv' => User::getByUUID($_SESSION['uuid'])['nickserv']));
    } else {
        $response->redirect('/error/401', 401);
    }
});

$this->respond('POST', 'settings/changepw', function($request, $response, $service) {
    if (Authentication::verifySession()) {
        User::changePassword($_SESSION['uuid'], $request->param('newpw'));
        $service->flash("Password has been updated");
    } else {
        $response->redirect('/error/401', 401);
    }
});

$this->respond('POST', 'settings/changens', function($request, $response) {
    if (Authentication::verifySession()) {
        User::changeNickserv($_SESSION['uuid'], $request->param('nickserv'));
    } else {
        $response->redirect('/error/401', 401);
    }
});

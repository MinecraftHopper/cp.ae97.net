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
        $response->redirect("/auth/login", 302);
    }
});

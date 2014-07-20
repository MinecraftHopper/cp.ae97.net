<?php

namespace CP\User;

$this->respond('GET', '/settings', function($request, $response, $service, $app) {
    if (verifySession($app)) {
        $service->render('index.phtml', array('action' => 'settings', 'page' => 'user/settings.phtml'));
    } else {
        $response->redirect("/auth/login", 302);
    }
});

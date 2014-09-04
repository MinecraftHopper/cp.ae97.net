<?php

$this->respond('GET', '/settings', function($request, $response, $service, $app) {
    if (verifySession($app)) {
        $service->render('index.phtml', array('action' => 'settings', 'page' => 'cp/user/settings.phtml'));
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$this->with('/admin', __DIR__ . '/admin/index.php');

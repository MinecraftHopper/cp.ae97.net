<?php

use \AE97\Panel\Authentication;

$this->respond('GET', '/settings', function($request, $response, $service, $app) {
    if (Authentication::verifySession($app)) {
        $service->render(HTML_DIR . 'index.phtml', array('action' => 'settings', 'page' => HTML_DIR . 'cp/user/settings.phtml'));
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$this->with('/admin', __DIR__ . '/admin/index.php');

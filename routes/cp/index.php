<?php

$this->respond('GET', '/settings', function($request, $response, $service, $app) {
    if (verifySession($app)) {
        $service->render(HTML_DIR . 'index.phtml', array('action' => 'settings', 'page' => HTML_DIR . 'cp/user/settings.phtml'));
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$this->with('/admin', __DIR__ . '/admin/index.php');

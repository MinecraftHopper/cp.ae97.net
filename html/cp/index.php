<?php

$this->respond('GET', '/bot', function($request, $response, $service, $app) {
    if (verifySession($app)) {
        $service->render('index.phtml', array('action' => 'bot', 'page' => 'cp/admin/bot.phtml'));
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$this->respond('GET', '/user/permissions', function($request, $response, $service, $app) {
    if (verifySession($app) && checkPermission($app, 'user.view') && checkPermission($app, 'user.editperms')) {
        try {
            $statement = $app->auth_db->prepare("SELECT userId AS id, username AS name FROM users");
            $statement->execute();
            $resultSet = $statement->fetchAll();
        } catch (PDOException $ex) {
            error_log(addSlashes($ex->getMessage()) . "\r");
            $resultSet = array();
        }
        $service->render('index.phtml', array('action' => 'user', 'page' => 'cp/admin/user/permissions.phtml', 'users' => $resultSet));
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$this->respond('GET', '/user/editpermissions/[i:id]', function($request, $response, $service, $app) {
    if (verifySession($app) && checkPermission($app, 'user.view') && checkPermission($app, 'user.editperms')) {
        try {
            $statement = $app->auth_db->prepare("SELECT userId, username FROM users");
            $statement->execute();
            $resultSet = $statement->fetchAll();
        } catch (PDOException $ex) {
            error_log(addSlashes($ex->getMessage()) . "\r");
            $resultSet = array();
        }
        $service->render('index.phtml', array('action' => 'user', 'page' => 'cp/admin/user/editpermissions.phtml'));
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$this->respond('GET', '/user/approve', function($request, $response, $service, $app) {
    if (verifySession($app) && checkPermission($app, 'panel.viewusers')) {
        $perms['approveUser'] = checkPermission($app, 'panel.approveuser');
        $perms['deleteUser'] = checkPermission($app, 'panel.deleteuser');
        $service->render('index.phtml', array('action' => 'user', 'page' => 'cp/admin/user/approval.phtml', 'perms' => $perms));
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$this->respond('GET', '/ban', function($request, $response, $service, $app) {
    if (verifySession($app)) {
        $service->render('index.phtml', array('action' => 'ban', 'page' => 'cp/admin/ban.phtml'));
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$this->respond('/user/list/unapproved', function($request, $response, $service, $app) {
    if (verifySession($app)) {
        $perms['view'] = checkPermission($app, 'panel.viewuser');
        if ($perms['view']) {
            try {
                $statement = $app->auth_db->prepare("SELECT uuid as id,username as user,email FROM users WHERE approved=0 and verified=1");
                $statement->execute();
                $accounts = $statement->fetchAll();
            } catch (PDOException $ex) {
                error_log(addSlashes($ex->getMessage()) . "\r");
                $accounts = array();
            }
        } else {
            $accounts = array();
        }
        echo json_encode($accounts);
    } else {
        echo "failed";
    }
});

$this->respond('POST', '/user/approve/[i:id]', function($request, $response, $service, $app) {
    if (verifySession($app)) {
        try {
            $statement = $app->auth_db->prepare("UPDATE users SET approved=1 WHERE uuid=?");
            $statement->execute(array($request->id));
        } catch (PDOException $ex) {
            error_log(addSlashes($ex->getMessage()) . "\r");
        }
        $response->redirect("/user", 302);
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$this->respond('POST', '/user/delete/[i:id]', function($request, $response, $service, $app) {
    if (verifySession($app)) {
        try {
            $statement = $app->auth_db->prepare("DELETE FROM users WHERE uuid=?");
            $statement->execute(array($request->id));
        } catch (PDOException $ex) {
            error_log(addSlashes($ex->getMessage()) . "\r");
        }
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$this->respond('GET', '/settings', function($request, $response, $service, $app) {
    if (verifySession($app)) {
        $service->render('index.phtml', array('action' => 'settings', 'page' => 'cp/user/settings.phtml'));
    } else {
        $response->redirect("/auth/login", 302);
    }
});

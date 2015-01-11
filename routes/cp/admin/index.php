<?php

use \AE97\Panel\Authentication,
    \AE97\Panel\Utilities,
    \AE97\Validate,
    \PDOException;

$this->respond('GET', '/', function($request, $response, $service, $app) {
    if (Authentication::verifySession($app)) {
        $service->render(HTML_DIR . 'index.phtml', array('action' => 'index', 'page' => HTML_DIR . 'cp/admin/index.phtml'));
    } else {
        $response->redirect("/auth/login", 302)->send();
    }
});

$this->respond('GET', '/bot', function($request, $response, $service, $app) {
    if (Authentication::verifySession($app)) {
        $service->render(HTML_DIR . 'index.phtml', array('action' => 'bot', 'page' => HTML_DIR . 'cp/admin/bot/index.phtml'));
    } else {
        $response->redirect("/auth/login", 302)->send();
    }
});

$this->respond('GET', '/user/approve', function($request, $response, $service, $app) {
    if (Authentication::verifySession($app) && Authentication::checkPermission($app, 'panel.viewusers')) {
        $perms['approveUser'] = Authentication::checkPermission($app, 'panel.approveuser');
        $perms['deleteUser'] = Authentication::checkPermission($app, 'panel.deleteuser');
        $service->render(HTML_DIR . 'index.phtml', array('action' => 'user', 'page' => HTML_DIR . 'cp/admin/user/approval.phtml', 'perms' => $perms));
    } else {
        $response->redirect("/auth/login", 302)->send();
    }
});

$this->respond('GET', '/user/manage', function($request, $response, $service, $app) {
    if (Authentication::verifySession($app) && Authentication::checkPermission($app, 'panel.viewusers')) {
        $users = $app->auth_db->prepare("SELECT `uuid` AS `id`,`username` FROM users");
        $users->execute();
        $service->render(HTML_DIR . 'index.phtml', array('action' => 'user', 'page' => HTML_DIR . 'cp/admin/user/manage.phtml', 'users' => $users->fetchAll()));
    } else {
        $response->redirect("/auth/login", 302)->send();
    }
});

$this->respond('GET', '/user/edit', function($request, $response, $service, $app) {
    if (Authentication::verifySession($app) && Authentication::checkPermission($app, 'panel.viewusers')) {
        if ($request->param('name') == null) {
            $response->redirect('/cp/admin/user/manage', 302)->send();
            return;
        }
        $userstmt = $app->auth_db->prepare("SELECT uuid, username, email, verified, approved FROM users WHERE username = ?");
        $userstmt->execute(array($request->param('name')));
        $user = $userstmt->fetch();
        if ($user == null) {
            $response->redirect('/cp/admin/user/manage', 302)->send();
            return;
        }
        $permstmt = $app->auth_db->prepare("SELECT perm FROM permissions");
        $permstmt->execute();
        $permList = $permstmt->fetchAll();

        $userpermstmt = $app->auth_db->prepare("SELECT permission FROM userperms WHERE userId = ?");
        $userpermstmt->execute(array($user['uuid']));
        $userPermsTemp = $userpermstmt->fetchAll();

        $userPerms = array();
        foreach ($userPermsTemp as $perm) {
            $userPerms[$perm['permission']] = true;
        }

        $service->render(HTML_DIR . 'index.phtml', array('action' => 'user', 'page' => HTML_DIR . 'cp/admin/user/edit.phtml', "user" => $user, "allPerms" => $permList, "userPerms" => $userPerms));
    } else {
        $response->redirect("/auth/login", 302)->send();
    }
});

$this->respond('GET', '/ban', function($request, $response, $service, $app) {
    if (Authentication::verifySession($app)) {

        $casted = array();
        $record = array();

        foreach ($record as $id => $ban) {
            $existing = $casted[$id];
            if ($existing === null) {
                $existing = array(
                    'id' => $id,
                    'issuer' => $ban['issuedBy'],
                    'kickmessage' => $ban['kickMessage'],
                    'issueDate' => $ban['issueDate'],
                    'type' => $ban['type'] === 0 ? "standard" : "extended",
                    'channels' => array($ban['channel'])
                );
            } else {
                $existing['channels'][] = $ban['channel'];
            }
            $casted[id] = $existing;
        }
        $service->render(HTML_DIR . 'index.phtml', array('action' => 'ban', 'page' => HTML_DIR . 'cp/admin/ban/index.phtml', 'bans' => $casted));
    } else {
        $response->redirect("/auth/login", 302)->send();
    }
});

$this->respond('GET', '/user', function($request, $response, $service, $app) {
    if (Authentication::verifySession($app)) {
        if (Authentication::checkPermission($app, 'panel.viewusers')) {
            $perms['approveUser'] = Authentication::checkPermission($app, 'panel.approveuser');
            $perms['deleteUser'] = Authentication::checkPermission($app, 'panel.deleteuser');
            $service->render(HTML_DIR . 'index.phtml', array('action' => 'user', 'page' => HTML_DIR . 'cp/admin/user/approval.phtml', 'perms' => $perms));
        }
    } else {
        $response->redirect("/auth/login", 302)->send();
    }
});

$this->respond('POST', '/user/list/unapproved', function($request, $response, $service, $app) {
    if (Authentication::verifySession($app)) {
        $perms['view'] = Authentication::checkPermission($app, 'panel.viewuser');
        if ($perms['view']) {
            try {
                $statement = $app->auth_db->prepare("SELECT uuid as id,username as user,email FROM users WHERE approved=0 and verified=1");
                $statement->execute();
                $accounts = $statement->fetchAll();
            } catch (PDOException $ex) {
                logError($ex);
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

$this->respond('POST', '/user/approve/[:id]', function($request, $response, $service, $app) {
    if (Authentication::verifySession($app)) {
        try {
            $statement = $app->auth_db->prepare("UPDATE users SET approved=1 WHERE uuid=?");
            $statement->execute(array($request->id));
        } catch (PDOException $ex) {
            Utilities::logError($ex);
        }
        $response->redirect("/user", 302);
    } else {
        $response->redirect("/auth/login", 302)->send();
    }
});

$this->respond('POST', '/user/delete/[:id]', function($request, $response, $service, $app) {
    if (Authentication::verifySession($app)) {
        try {
            $statement = $app->auth_db->prepare("DELETE FROM users WHERE uuid=?");
            $statement->execute(array($request->id));
        } catch (PDOException $ex) {
            Utilities::logError($ex);
        }
    } else {
        $response->redirect("/auth/login", 302)->send();
    }
});

$this->respond('POST', '/user/edit', function($request, $response, $service, $app) {
    Validate::param($request->param('user'))->notNull();
    Validate::param($request->param('perms'))->notNull();
    try {
        $database = $app->auth_db;

        $useridStmt = $database->prepare("SELECT uuid FROM users WHERE username = ?");
        $useridStmt->execute(array($request->param('user')));
        $user = $useridStmt->fetch();
        if (!isset($user['uuid'])) {
            return;
        }
        $uuid = $user['uuid'];

        $database->beginTransaction();
        $database->prepare("DELETE FROM userperms WHERE userId = ?")->execute(array($uuid));
        foreach ($request->param('perms') as $perm) {
            $database->prepare("INSERT INTO userperms VALUES(?,?)")->execute(array($uuid, $perm));
        }
        $database->commit();
    } catch (PDOException $ex) {
        Utilities::logError($ex);
        try {
            $app->auth_db->rollBack();
        } catch (Exception $e) {
            Utilities::logError($e);
        }
    }
});

$this->respond('GET', '/bans/get', function($request, $response, $service, $app) {
    $page = $request->param('p');
    if ($page === null) {
        $page = 1;
    }
    $page--;
    if (Authentication::verifySession($app) && Authentication::checkPermission($app, "")) {
        try {
            $statement = $app->auth_db->prepare("SELECT id, issuedBy, kickMessage, issueDate, channel, type "
                  . "FROM bans "
                  . "INNER JOIN banchannels ON bans.id = banId "
                  . "ORDER BY id "
                  . "LIMIT " . strval(intval($page) * 10) . ", 10");
            $statement->execute();
            $record = $statement->fetchAll(PDO::FETCH_ASSOC);
            $casted = array();
        } catch (Exception $ex) {
            return $ex;
        }

        foreach ($record as $ban) {
            if (!isset($casted[$ban['id']])) {
                $casted[$ban['id']] = array(
                    'id' => $ban['id'],
                    'issuer' => $ban['issuedBy'],
                    'kickmessage' => $ban['kickMessage'],
                    'issueDate' => $ban['issueDate'],
                    'type' => $ban['type'] === 0 ? "standard" : "extended",
                    'channels' => array($ban['channel'])
                );
            } else {
                $casted[$ban['id']]['channels'][] = $ban['channel'];
            }
        }
        return json_encode($casted);
    } else {
        return '{msg="failed"}';
    }
});

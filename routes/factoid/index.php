<?php

use \AE97\Panel\Authentication,
    \AE97\Panel\Utilities,
    \AE97\Panel\Factoids,
    \PDOException;

$this->respond('GET', '/?', function($request, $response, $service, $app) {
    $perms = array('edit' => false, 'delete' => false);
    if (Authentication::verifySession($app)) {
        $perms['edit'] = Authentication::checkPermission($app, 'factoids.edit');
        $perms['delete'] = Authentication::checkPermission($app, 'factoids.remove');
    }
    $db = $request->param('db');
    if ($db == null || $db == '') {
        $db = 'Global';
    }
    $service->render(HTML_DIR . 'index.phtml', array('action' => 'factoid', 'page' => HTML_DIR . 'factoid/factoid.phtml', 'perms' => $perms, 'db' => $db));
});

$this->respond('GET', '/edit/[i:id]', function($request, $response, $service, $app) {
    if (Authentication::verifySession($app)) {
        try {
            if (Authentication::checkPermission($app, 'factoids.edit')) {
                $factoidManager = new Factoids($app->factoid_db);
                $factoids = $factoidManager->getFactoid($request->param('id'));
                $service->render(HTML_DIR . 'index.phtml', array('action' => 'factoid', 'page' => HTML_DIR . 'factoid/edit.phtml', 'id' => $factoids['id'], 'name' => $factoids['name'], 'content' => $factoids['content'], 'game' => $factoids['game'], 'mode' => 'Edit'));
            }
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return array('msg' => 'Failed, MySQL database returned error');
        }
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$this->respond('GET', '/new', function($request, $response, $service, $app) {
    if (Authentication::verifySession($app)) {
        try {
            if (Authentication::checkPermission($app, 'factoids.create')) {
                $statement = $app->factoid_db->prepare("SELECT displayname,idname FROM games");
                $statement->execute();
                $dbs = $statement->fetchAll(PDO::FETCH_ASSOC);
                $service->render(HTML_DIR . 'index.phtml', array('action' => 'factoid', 'page' => HTML_DIR . 'factoid/new.phtml', "dbs" => $dbs));
            }
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return array('msg' => 'Failed, MySQL database returned error');
        }
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$this->respond('POST', '/submit-new', function($request, $response, $service, $app) {
    if (Authentication::verifySession($app) && Authentication::checkPermission($app, 'factoids.create')) {
        try {
            $app->factoid_db
                  ->prepare("INSERT INTO factoids (name, game, content) VALUES (?, (SELECT games.id FROM games WHERE idname=?), ?) "
                        . "ON DUPLICATE KEY UPDATE content = ?")
                  ->execute(array($request->param('name'), $request->param('game'), $request->param('content'), $request->param('content')));
            $service->flash("Successfully created new factoid");
            $response->redirect('/factoid?db=' . $request->param('game'), 302);
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            $service->flash("Failed to create factoid");
        }
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$this->respond('GET', '/delete/[i:id]', function($request, $response, $service, $app) {
    if (Authentication::verifySession($app)) {
        try {
            if (Authentication::checkPermission($app, 'factoids.delete')) {
                $gameStmt = $app->factoid_db
                      ->prepare("SELECT displayname AS game FROM games "
                      . "INNER JOIN factoids ON factoids.game = games.id WHERE factoids.id = ?");
                $gameStmt->execute(array($request->param('id')));
                $game = $gameStmt->fetch()['game'];
                $app->factoid_db->prepare("DELETE FROM factoids WHERE id=?")->execute(array($request->param('id')));
            }
        } catch (PDOException $ex) {
            Utilities::logError($ex);
        }
        $response->redirect('/factoid?db=' . $game);
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$this->respond('POST', '/submit-edit', function($request, $response, $service, $app) {
    if (Authentication::verifySession($app)) {
        try {
            if (Authentication::checkPermission($app, 'factoids.edit')) {
                $id = $request->param('id');
                $factoidContext = str_replace("\n", ";;", $request->param('content'));
                $factoidManager = new Factoids($app->factoid_db);
                $factoidManager->editFactoid($id, $factoidContext);
                $game = $factoidManager->getGame($id);
                $response->redirect('/factoid?db=' . $game['id'], 302);
            }
        } catch (Exception $ex) {
            Utilities::logError($ex);
        }
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$this->respond('POST', '/get', function($request, $response, $service, $app) {
    $factoidManager = new Factoids($app->factoid_db);
    if ($request->param('db') == null) {
        $db = $factoidManager->getDatabase();
    } else {
        $db = $factoidManager->getDatabase($request->param('db'));
    }
    echo json_encode($db);
});

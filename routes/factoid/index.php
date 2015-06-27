<?php

use \AE97\Panel\Authentication,
    \AE97\Panel\Utilities,
    \AE97\Panel\Factoids;

$this->respond('GET', '/?', function($request, $response, $service) {
    $perms = array('edit' => false, 'delete' => false);
    if (Authentication::verifySession()) {
        $perms['edit'] = Authentication::checkPermission('factoids.edit');
        $perms['delete'] = Authentication::checkPermission('factoids.delete');
    }
    $db = $request->param('db');
    if ($db == null || $db == '') {
        $db = 'Global';
    }
    $service->render(HTML_DIR . 'index.phtml', array('action' => 'factoid', 'page' => HTML_DIR . 'factoid/factoid.phtml', 'perms' => $perms, 'db' => $db));
});

$this->respond('GET', '/edit/[i:id]', function($request, $response, $service) {
    if (Authentication::verifySession()) {
        try {
            if (Authentication::checkPermission('factoids.edit')) {
                $factoids = Factoids::getFactoid($request->param('id'));
                $service->render(HTML_DIR . 'index.phtml', array('action' => 'factoid', 'page' => HTML_DIR . 'factoid/edit.phtml', 'id' => $factoids['id'], 'name' => $factoids['name'], 'content' => str_replace(';;', "\n", $factoids['content']), 'game' => $factoids['game'], 'mode' => 'Edit'));
            }
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return array('msg' => 'Failed, MySQL database returned error');
        }
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$this->respond('GET', '/new', function($request, $response, $service) {
    if (Authentication::verifySession()) {
        try {
            if (Authentication::checkPermission('factoids.create')) {
                $dbs = Factoids::getDatabaseNames();
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

$this->respond('POST', '/submit-new', function($request, $response, $service) {
    if (Authentication::verifySession() && Authentication::checkPermission('factoids.create')) {
        try {
            Factoids::createFactoid($request->param('game'), $request->param('name'), $request->param('content'));
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

$this->respond('GET', '/delete/[i:id]', function($request, $response) {
    if (Authentication::verifySession()) {
        try {
            if (Authentication::checkPermission('factoids.delete')) {
                Factoids::deleteFactoid($request->param('id'));
            }
        } catch (PDOException $ex) {
            Utilities::logError($ex);
        }
        $response->redirect('/factoid');
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$this->respond('POST', '/submit-edit', function($request, $response) {
    if (Authentication::verifySession()) {
        try {
            if (Authentication::checkPermission('factoids.edit')) {
                $id = $request->param('id');
                $factoidContext = str_ireplace(array("\r\n", "\r", "\n"), ";;", $request->param('content'));
                Factoids::editFactoid($id, $factoidContext);
                $game = Factoids::getGame($id);
                $response->redirect('/factoid?db=' . $game['id'], 302);
            }
        } catch (Exception $ex) {
            Utilities::logError($ex);
        }
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$this->respond('POST', '/get', function($request) {
    if ($request->param('db') == null) {
        $db = Factoids::getDatabase();
    } else {
        $db = Factoids::getDatabase($request->param('db'));
    }
    echo json_encode($db);
});

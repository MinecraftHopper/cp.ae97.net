<?php

$this->respond('GET', '/?', function($request, $response, $service, $app) {
    $response->redirect("/factoid/db/global", 302);
});

$this->respond('GET', '/db/[a:db]?', function($request, $response, $service, $app) {
    $perms = array('edit' => false, 'delete' => false);
    if (verifySession($app)) {
        $perms['edit'] = checkPermission($app, 'factoids.edit');
        $perms['delete'] = checkPermission($app, 'factoids.remove');
    }
    $db = $request->param('db');
    if ($db == null || $db == '') {
        $db = 'Global';
    }
    $service->render('index.phtml', array('action' => 'factoid', 'page' => 'factoid/factoid.phtml', 'perms' => $perms, 'db' => $db));
});

$this->respond('GET', '/edit/[i:id]', function($request, $response, $service, $app) {
    if (verifySession($app)) {
        try {
            if (checkPermission($app, 'factoids.edit')) {
                $statement = $app->factoid_db
                      ->prepare("SELECT factoids.id AS id,name,content,games.displayname AS game "
                      . "FROM factoids "
                      . "INNER JOIN games ON factoids.game = games.id "
                      . "WHERE factoids.id=? "
                      . "LIMIT 1");
                $statement->execute(array($request->param('id')));
                $factoids = $statement->fetch();
                $service->render('index.phtml', array('action' => 'factoid', 'page' => 'factoid/edit.phtml', 'id' => $factoids['id'], 'name' => $factoids['name'], 'content' => $factoids['content'], 'game' => $factoids['game'], 'mode' => 'Edit'));
            }
        } catch (PDOException $ex) {
            logError($ex);
            return array('msg' => 'Failed, MySQL database returned error');
        }
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$this->respond('GET', '/new', function($request, $response, $service, $app) {
    if (verifySession($app)) {
        try {
            if (checkPermission($app, 'factoids.create')) {
                $statement = $app->factoid_db->prepare("SELECT displayname,idname FROM games");
                $statement->execute();
                $dbs = $statement->fetchAll(PDO::FETCH_ASSOC);
                $service->render('index.phtml', array('action' => 'factoid', 'page' => 'factoid/new.phtml', "dbs" => $dbs));
            }
        } catch (PDOException $ex) {
            logError($ex);
            return array('msg' => 'Failed, MySQL database returned error');
        }
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$this->respond('POST', '/submit-new', function($request, $response, $service, $app) {
    if (verifySession($app) && checkPermission($app, 'factoids.create')) {
        try {
            $app->factoid_db
                  ->prepare("INSERT INTO factoids (name, game, content) VALUES (?, (SELECT games.id FROM games WHERE idname=?), ?) "
                        . "ON DUPLICATE KEY UPDATE content = ?")
                  ->execute(array($request->param('name'), $request->param('game'), $request->param('content'), $request->param('content')));
            $service->flash("Successfully created new factoid");
            $response->redirect('/factoid/db/' . $request->param('game'), 302);
        } catch (PDOException $ex) {
            logError($ex);
            $service->flash("Failed to create factoid");
        }
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$this->respond('GET', '/delete/[i:id]', function($request, $response, $service, $app) {
    if (verifySession($app)) {
        try {
            error_log('test');
            if (checkPermission($app, 'factoids.delete')) {
                $gameStmt = $app->factoid_db
                      ->prepare("SELECT displayname AS game FROM games "
                      . "INNER JOIN factoids ON factoids.game = games.id WHERE factoids.id = ?");
                $gameStmt->execute(array($request->param('id')));
                $game = $gameStmt->fetch()['game'];
                $app->factoid_db->prepare("DELETE FROM factoids WHERE id=?")->execute(array($request->param('id')));
            }
        } catch (PDOException $ex) {
            logError($ex);
        }
        $response->redirect('/factoid/db/' . $game);
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$this->respond('POST', '/submit-edit', function($request, $response, $service, $app) {
    if (verifySession($app)) {
        try {
            if (checkPermission($app, 'factoids.edit')) {
                $id = $request->param('id');
                $app->factoid_db->prepare("UPDATE factoids SET content = ? WHERE id = ?")->execute(array($request->param('content'), $id));
                $statement = $app->factoid_db
                      ->prepare("SELECT games.displayname AS game "
                      . "FROM factoids "
                      . "INNER JOIN games ON (factoids.game = games.id) "
                      . "WHERE factoids.id=?");
                $statement->execute(array($id));
                $game = $statement->fetch();
                $response->redirect('/factoid/db/' . $game['game'], 302);
                return json_encode(array('msg' => 'Success, changed to ' . $request->param('content'), 'game' => $game, 'id' => $id));
            }
            return array('msg' => 'Failed, no permissions to edit');
        } catch (PDOException $ex) {
            logError($ex);
            return array('msg' => 'Failed, MySQL database returned error');
        }
    } else {
        $response->redirect("/auth/login", 302);
    }
});

$this->respond('POST', '/get', function($request, $response, $service, $app) {
    $game = $request->param('db');
    if ($game == null || $game == '') {
        $game = 'global';
    }
    $database = $app->factoid_db;
    try {
        $gameliststatement = $database->prepare("SELECT id,idname,displayname FROM games");
        $gameliststatement->execute();
        $gamelist = $gameliststatement->fetchAll();
        $statement = $database->prepare("SELECT factoids.id,factoids.name, factoids.content, games.displayname "
              . "FROM factoid.factoids "
              . "INNER JOIN factoid.games ON (factoid.factoids.game = factoid.games.id) "
              . "WHERE factoid.games.idname = ?");
        $statement->execute(array(0 => $game));
        $factoids = $statement->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $ex) {
        logError($ex);
        echo "Error";
        return;
    }
    $firstCounter = 0;
    foreach ($gamelist as $gameitem):
        $compiledGamelist[$firstCounter] = array('idname' => $gameitem['idname'], 'displayname' => $gameitem['displayname']);
        if ($compiledGamelist[$firstCounter]['idname'] === $game) {
            $gameAskedFor = $compiledGamelist[$firstCounter];
        }
        $firstCounter++;
    endforeach;
    $compiledFactoidlist = array();
    foreach ($factoids as $f):
        array_push($compiledFactoidlist, array('id' => $f['id'], 'name' => $f['name'], 'content' => $f['content'], 'game' => $game == null ? $f['game'] : $game));
    endforeach;
    $collection = array();
    $collection['gamerequest'] = isset($gameAskedFor) ? $gameAskedFor : 'Minecraft';
    $collection['games'] = isset($compiledGamelist) ? $compiledGamelist : array();
    $collection['factoids'] = isset($compiledFactoidlist) ? $compiledFactoidlist : array();
    echo json_encode($collection);
});

<?php

$this->respond('GET', '/[|index|index.php:page]?', function($request, $response, $service, $app) {
    $perms = array();
    $perms['edit'] = checkPermission($app, 'factoids.edit');
    $perms['delete'] = checkPermission($app, 'factoids.remove');
    $service->render('index.phtml', array('action' => 'factoid', 'page' => 'factoid/factoid.phtml', 'perms' => $perms));
});

$this->respond('GET', '/edit/[i:id]', function($request, $response, $service, $app) {
    if (verifySession($app)) {
        try {
            if (checkPermission($app, 'factoids.edit')) {
                $statement = $app->factoid_db->prepare("SELECT id,name,content FROM factoids WHERE id=?");
                $statement->execute(array($request->param('id')));
                $factoids = $statement->fetchAll();
                foreach ($factoids as $factoid):
                    $service->render('index.phtml', array('action' => 'factoid', 'page' => 'factoid/edit.phtml', 'id' => $factoid['id'], 'name' => $factoid['name'], 'content' => $factoid['content']));
                endforeach;
            }
        } catch (PDOException $ex) {
            error_log(addSlashes($ex->getMessage()) . "\r");
            return array('msg' => 'Failed, MySQL database returned error');
        }
    } else {
        $response->redirect("/auth/login/factoid/edit/" . $request->param('id'), 302);
    }
});

$this->respond('GET', '/new/', function($request, $response, $service, $app) {
    if (verifySession($app)) {
        if (checkPermission($app, 'factoids.edit')) {
            
        }
    }
});

$this->respond('POST', '/delete', function($request, $response, $service, $app) {
    if (verifySession($app)) {
        try {
            
        } catch (PDOException $ex) {
            error_log(addSlashes($ex->getMessage()) . "\r");
        }
    } else {
        $response->redirect("/auth/login/factoid", 302);
    }
});

$this->respond('POST', '/submit-edit', function($request, $response, $service, $app) {
    if (verifySession($app)) {
        try {
            if (checkPermission($app, 'factoids.edit')) {
                $app->factoid_db->prepare("UPDATE factoids SET content = ?  WHERE id = ?")->execute(array($request->param('content'), $request->param('id')));
                $response->redirect("/factoid", 302);
                return json_encode(array('msg' => 'Success, changed to ' . $request->param('value')));
            }
            return array('msg' => 'Failed, no permissions to edit');
        } catch (PDOException $ex) {
            error_log(addSlashes($ex->getMessage()) . "\r");
            return array('msg' => 'Failed, MySQL database returned error');
        }
    } else {
        $response->redirect("/auth/login/factoid/" . $request->param('id'), 302);
    }
});

$this->respond('POST', '/new', function($request, $response, $service, $app) {
    if (verifySession($app)) {
        try {
            
        } catch (PDOException $ex) {
            error_log(addSlashes($ex->getMessage()) . "\r");
        }
    } else {
        $response->redirect("/auth/login/factoid/new", 302);
    }
});

$this->respond('/get', function($request, $response, $service, $app) {
    $game = $request->param('db');
    if ($game === null || $game === '') {
        $game = 'global';
    }
    $database = $app->factoid_db;
    try {
        $gameliststatement = $database->prepare("SELECT id,idname,displayname FROM games");
        $gameliststatement->execute();
        $gamelist = $gameliststatement->fetchAll();
        $statement = $database->prepare("SELECT factoids.id,factoids.name, factoids.content, games.displayname FROM factoid.factoids
          INNER JOIN factoid.games ON (factoid.factoids.game = factoid.games.id)
          WHERE factoid.games.idname = ?");
        $statement->execute(array($game));
        $factoids = $statement->fetchAll();
    } catch (PDOException $ex) {
        error_log(addSlashes($ex->getMessage()) . "\r");
        $factoids = array();
        $gamelist = array();
    }
    $compiledGamelist = array();
    $counter = 0;
    $gameAskedFor = array();
    foreach ($gamelist as $gameitem):
        $compiledGamelist[$counter] = array('idname' => $gameitem['idname'], 'displayname' => $gameitem['displayname']);
        if ($compiledGamelist[$counter]['idname'] === $game) {
            $gameAskedFor = $compiledGamelist[$counter];
        }
        $counter = $counter + 1;
    endforeach;
    $compiledFactoidlist = array();
    $counter = 0;
    foreach ($factoids as $f):
        $compiledFactoidlist[$counter] = array('id' => $f['id'], 'name' => $f['name'], 'content' => $f['content'], 'game' => $game == null ? $f['game'] : $game);
        $counter = $counter + 1;
    endforeach;
    $collection = array();
    $collection['gamerequest'] = $gameAskedFor;
    $collection['games'] = $compiledGamelist;
    $collection['factoids'] = $compiledFactoidlist;
    echo json_encode($collection);
});

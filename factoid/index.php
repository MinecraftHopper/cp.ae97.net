<?php

$this->respond('GET', '/[|index|index.php:page]?', function($request, $response, $service, $app) {
  $game = $request->param("db");
  $perms['edit'] = checkPermission($app, 'editentry', 'perms_factoid');
  $perms['delete'] = checkPermission($app, 'removeentry', 'perms_factoid');
  $service->render('index.phtml', array('action' => 'factoid', 'page' => 'factoid/factoid.phtml', 'perms' => $perms));
});

$this->respond('POST', '/delete', function($request, $response, $service, $app) {
  if (verifySession($app)) {
    try {
      
    } catch (PDOException $ex) {
      error_log(addSlashes($ex->getMessage()) . "\r");
    }
  } else {
    $response->redirect("/auth/login", 302);
  }
});

$this->respond('POST', '/edit', function($request, $response, $service, $app) {
  if (verifySession($app)) {
    try {
      if (checkPermission($app, 'editentry', 'perms_factoid')) {
        echo $request->param('name') + '\n';
        //echo $request->param('content') + '\n';
        $app->db->prepare("UPDATE factoids SET name = ?, content = ?  WHERE id = ?")->execute(array($request->param('name'), $request->param('content'), $request->param('id')));
        return "Success";
      }
      return "Failed";
    } catch (PDOException $ex) {
      error_log(addSlashes($ex->getMessage()) . "\r");
      return "Failed";
    }
  } else {
    $response->redirect("/auth/login", 302);
  }
});

$this->respond('POST', '/new', function($request, $response, $service, $app) {
  if (verifySession($app)) {
    try {
      
    } catch (PDOException $ex) {
      error_log(addSlashes($ex->getMessage()) . "\r");
    }
  } else {
    $response->redirect("/auth/login", 302);
  }
});

$this->respond('/get', function($request, $response, $service, $app) {
  $game = $request->param('db');
  $database = $app->db;
  try {
    $gameliststatement = $database->prepare("SELECT idname,displayname FROM games");
    $gameliststatement->execute();
    $gamelist = $gameliststatement->fetchAll();
    if ($game == null) {
      $statement = $database->prepare("SELECT id,name,content,game FROM factoids");
      $statement->execute();
    } else {
      $statement = $database->prepare("SELECT id,name,content FROM factoids WHERE game=?");
      $statement->execute(array($game));
    }
    $factoids = $statement->fetchAll();
  } catch (PDOException $ex) {
    error_log(addSlashes($ex->getMessage()) . "\r");
    $factoids = array();
    $gamelist = array();
  }
  $compiledGamelist = array();
  $counter = 0;
  foreach ($gamelist as $gameitem):
    $compiledGamelist[$counter] = array('idname' => $gameitem['idname'], 'displayname' => $gameitem['displayname']);
    $counter++;
  endforeach;
  $compiledFactoidlist = array();
  $counter = 0;
  foreach ($factoids as $f):
    $compiledFactoidlist[$counter] = array('id' => $f['id'], 'name' => $f['name'], 'content' => $f['content'], 'game' => $game == null ? $f['game'] : $game);
    $counter++;
  endforeach;
  $collection = array();
  $collection['games'] = $compiledGamelist;
  $collection['factoids'] = $compiledFactoidlist;
  $perms = array();
  $perms['edit'] = checkPermission($app, 'editentry', 'perms_factoid');
  $perms['delete'] = checkPermission($app, 'removeentry', 'perms_factoid');
  $collection['perms'] = $perms;
  echo json_encode($collection);
});

include('functions/functions.php');

<?php

$this->respond('GET', '/[|index|index.php:page]?', function($request, $response, $service, $app) {
  $game = $request->param("db");
  try {
    $gameliststatement = $app->db->prepare("SELECT idname,displayname FROM games");
    $gameliststatement->execute();
    $gamelist = $gameliststatement->fetchAll();
    if ($game == null) {
      $statement = $app->db->prepare("SELECT id,name,content,game FROM factoids");
      $statement->execute();
    } else {
      $statement = $app->db->prepare("SELECT id,name,content FROM factoids WHERE game=?");
      $statement->execute(array($game));
    }
    $factoids = $statement->fetchAll();
  } catch (PDOException $ex) {
    error_log(addSlashes($ex->getMessage()) . "\r");
    $factoids = array();
    $gamelist = array();
  }
  $perms['edit'] = checkPermission($app, 'editentry', 'perms_factoid');
  $perms['delete'] = checkPermission($app, 'removeentry', 'perms_factoid');
  $service->render('index.phtml', array('action' => 'factoid', 'page' => 'factoid/factoid.phtml', 'factoids' => $factoids, 'perms' => $perms, 'game' => $game, 'gamelist' => $gamelist));
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
      
    } catch (PDOException $ex) {
      error_log(addSlashes($ex->getMessage()) . "\r");
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

$this->respond('POST', '/get', function($request, $response, $service, $app) {
  if (verifySession($app)) {
    try {
      
    } catch (PDOException $ex) {
      error_log(addSlashes($ex->getMessage()) . "\r");
    }
  } else {
    $response->redirect("/auth/login", 302);
  }
});

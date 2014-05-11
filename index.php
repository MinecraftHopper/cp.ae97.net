<?php

session_start();
if (empty($_SESSION['count'])) {
  $_SESSION['count'] = 1;
} else {
  $_SESSION['count'] ++;
}
require_once __DIR__ . '/vendor/autoload.php';
$klein = new \Klein\Klein();

$blocked = array(
    "/composer.[phar|lock|json:format]",
    "/vendor",
    "/klein",
    "/mailgun"
);

foreach ($blocked as $key => $target):
  $klein->respond($target, function($request, $response, $service, $app) {
    $response->redirect("/404", 302);
  });
endforeach;

$klein->respond(function($request, $response, $service, $app) {
  $app->register('db', function() {
    require('../configs/config.php');
    $db = new PDO("mysql:host=" . $_DATABASE['host'] . ";dbname=" . $_DATABASE['db'], $_DATABASE['user'], $_DATABASE['pass']);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
  });
  $app->register('mail', function() {
    require('../configs/config.php');
    $mail = new Mailgun\Mailgun($_MAILGUN['key']);
    return $mail;
  });
  $app->register('email', function() {
    require('../configs/config.php');
    return $_MAILGUN['email'];
  });
  $app->register('domain', function() {
    require('../configs/config.php');
    return $_SITE['domain'];
  });
  $app->register('site', function() {
    require('../configs/config.php');
    return $_SITE['site'];
  });
  $app->register('fullsite', function() {
    require('../configs/config.php');
    return $_SITE['fullsite'];
  });
});

$klein->respond('/ban', function($request, $response, $service, $app) {
  $response->redirect("/auth/ban", 302);
});

$klein->respond('/user', function($request, $response, $service, $app) {
  $response->redirect("/auth/user", 302);
});

$klein->respond('/bot', function($request, $response, $service, $app) {
  $response->redirect("/auth/bot", 302);
});

$klein->respond('GET', '/[|index|index.php:page]?', function($request, $response, $service, $app) {
  $service->render('index.phtml', array('action' => 'welcome', 'page' => 'components/welcome.phtml'));
});

$klein->respond('GET', '/factoid', function($request, $response, $service, $app) {
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
  $service->render('index.phtml', array('action' => 'factoid', 'page' => 'components/factoid.phtml', 'factoids' => $factoids, 'perms' => $perms, 'game' => $game, 'gamelist' => $gamelist));
});

$klein->respond('GET', '/settings', function($request, $response, $service, $app) {
  if (verifySession($app)) {
    $service->render('index.phtml', array('action' => 'settings', 'page' => 'components/settings.phtml'));
  } else {
    $response->redirect("/auth/login", 302);
  }
});

$klein->with('/auth', 'auth/index.php');
$klein->with('/admin', 'admin/index.php');

$klein->respond('POST', '/factoid', function($request, $response, $service, $app) {
  if (verifySession($app)) {
    try {
      
    } catch (PDOException $ex) {
      error_log(addSlashes($ex->getMessage()) . "\r");
    }
  } else {
    $response->redirect("/auth/login", 302);
  }
});

$klein->respond('404', function($request, $response, $service, $app) {
  $service->render('index.phtml', array('action' => '404', 'try' => $request));
});

$klein->onError(function($klein, $err_msg) {
  $klein->service()->flash("Error: " . $err_msg);
  $klein->service()->back();
});

$klein->dispatch();

function verifySession($app) {
  if (!isset($_SESSION['authkey']) || !isset($_SESSION['session']) || $_SESSION['authkey'] == null || $_SESSION['session'] == null) {
    return false;
  } else {
    try {
      $statement = $app->db->prepare("SELECT authkey, session FROM auth WHERE authkey = ?");
      $statement->execute(array($_SESSION["authkey"]));
      $statement->setFetchMode(PDO::FETCH_ASSOC);
      $db = $statement->fetch();
      return validateSession($db, $_SESSION);
    } catch (PDOException $ex) {
      error_log(addSlashes($ex->getMessage()) . "\r");
      $_SESSION['authkey'] = null;
      $_SESSION['session'] = null;
      return false;
    }
  }
}

function validateSession($db, $session) {
  if (!isset($db['session']) || !isset($db['authkey']) || !isset($session['authkey']) || !isset($session['authkey'])) {
    $session ['authkey'] = null;
    $session ['session'] = null;
    return false;
  }
  if ($session['authkey'] == $db['authkey'] && $session['session'] == $db['session']) {
    return true;
  } else {
    $session['authkey'] = null;
    $session['session'] = null;
    return false;
  }
}

function checkPermission($app, $perm, $table) {
  if (!verifySession($app)) {
    return false;
  } else {
    try {
      $statement = $app->db->prepare("SELECT " . $perm . " FROM " . $table . " WHERE authkey = ?");
      $statement->execute(array($_SESSION["authkey"]));
      $statement->setFetchMode(PDO::FETCH_ASSOC);
      $db = $statement->fetch();
      return isset($db[$perm]) && $db[$perm] === "1";
    } catch (PDOException $ex) {
      error_log(addSlashes($ex->getMessage()) . "\r");
      return false;
    }
  }
}

function generate_string($length) {
  $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  $str = '';
  $count = strlen($charset);
  while ($length--) {
    $str .= $charset[mt_rand(0, $count - 1)];
  }
  return $str;
}

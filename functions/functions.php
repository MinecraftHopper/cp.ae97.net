<?php

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

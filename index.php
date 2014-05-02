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

$klein->respond('GET', '/[|index|index.php:page]?', function($request, $response, $service, $app) {
  $service->render("components/index.phtml", array('action' => 'welcome', 'page' => 'components/welcome.phtml'));
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
  $service->render("components/index.phtml", array('action' => 'factoid', 'page' => 'components/factoid.phtml', 'factoids' => $factoids, 'perms' => $perms, 'game' => $game, 'gamelist' => $gamelist));
});

$klein->respond('GET', '/logout', function($request, $response, $service, $app) {
  if (!verifySession($app)) {
    $response->redirect("/", 302);
  }
  try {
    $statement = $app->db->prepare("UPDATE auth SET session = ? WHERE authkey = ?");
    $statement->execute(array('null', $_SESSION['authkey']));
  } catch (PDOException $ex) {
    error_log(addSlashes($ex->getMessage()) . "\r");
  }
  $_SESSION['authkey'] = null;
  $_SESSION['session'] = null;
  $service->render("components/index.phtml", array('action' => 'logout', 'page' => 'auth/logout.phtml'));
  $response->redirect("/index", 302);
});

$klein->respond('GET', '/login', function($request, $response, $service, $app) {
  if (verifySession($app)) {
    $response->redirect("/", 302);
  }
  $service->render("components/index.phtml", array('action' => 'login', 'page' => 'auth/login.phtml'));
});

$klein->respond('GET', '/register', function($request, $response, $service, $app) {
  if (!verifySession($app)) {
    $service->render("components/index.phtml", array('action' => 'register', 'page' => 'auth/register.phtml'));
  } else {
    $response->redirect("/index", 302);
  }
});

$klein->respond('GET', '/verify', function($request, $response, $service, $app) {
  try {
    $service->validateParam('email', 'Invalid email')->isLen(5, 256);
    $service->validateParam('key', 'Invalid verify key')->isLen(32);
    $statement = $app->db->prepare("SELECT data FROM auth WHERE email=?");
    $statement->execute(array($request->param("email")));
    $db = $statement->fetch();
    if ($request->param('key') == $db['data']) {
      $statement = $app->db->prepare("UPDATE auth SET verified = 1, data = null WHERE email=?");
      $statement->execute(array($request->param('email')));
      $service->flash('Your email has been verified');
      $response->redirect("/login", 302);
    } else {
      throw new Exception("Invalid verify key for the email");
    }
  } catch (Exception $e) {
    $service->flash('Error: ' . $e->getMessage());
    $response->redirect("/login", 302);
  }
});

$klein->respond('GET', '/resetpw', function($request, $response, $service, $app) {
  $service->render("components/index.phtml", array('action' => 'resetpw', 'page' => 'auth/resetpw.phtml'));
});

$klein->respond('GET', '/reset-pw', function($request, $response, $service, $app) {
  try {
    $service->validateParam('authkey', 'Invalid auth key');
    $service->validateParam('resetkey', 'Invalid reset key')->isLen(64);
  } catch (Exception $e) {
    $service->flash("Error: " . $e->getMessage());
    $response->redirect('/resetpw', 302);
  }
  try {
    $statement = $app->db->prepare("SELECT authkey,data,email,verified FROM auth WHERE authkey=?");
    $statement->execute(array($request->param('authkey')));
    $statement->setFetchMode(PDO::FETCH_ASSOC);
    $db = $statement->fetch();
    if (!isset($db['data']) || !isset($db['authkey'])) {
      $service->flash("Error: No reset was requested for this account");
      $response->redirect('/resetpw', 302);
    } else if (!isset($db['verified']) || $db['verified'] == 0) {
      throw new Exception("Account not verified");
    } else if ($db['data'] == $request->param('resetkey')) {
      $unhashed = generate_string(16);
      $newpass = password_hash($unhashed, PASSWORD_DEFAULT);
      $app->db->prepare("UPDATE auth SET password = ?, data = null WHERE authkey = ?")->execute(array($newpass, $db['authkey']));
      $app->mail->sendMessage($app->domain, array('from' => 'Noreply <' . $app->email . '>',
          'to' => $db['email'],
          'subject' => 'New panel password', 'html' => 'Your password has been changed. Your new password is : ' . $unhashed));
      $service->flash('Your new password has been emailed to you');
      $response->redirect('/login', 302);
    } else {
      $service->flash("Error: Reset key has expired");
      $response->redirect('/resetpw', 302);
    }
  } catch (PDOException $ex) {
    error_log(addSlashes($ex->getMessage()) . "\r");
    $service->flash("Error: The MySQL connection has failed, please contact the admins");
    $response->redirect('/login', 302);
  }
});

$klein->respond('GET', '/settings', function($request, $response, $service, $app) {
  if (verifySession($app)) {
    $service->render("components/index.phtml", array('action' => 'settings', 'page' => 'components/settings.phtml'));
  } else {
    $response->redirect("/login", 302);
  }
});

$klein->respond('GET', '/bot', function($request, $response, $service, $app) {
  if (verifySession($app)) {
    $service->render("components/index.phtml", array('action' => 'bot', 'page' => 'components/bot.phtml'));
  } else {
    $response->redirect("/login", 302);
  }
});

$klein->respond('GET', '/user', function($request, $response, $service, $app) {
  if (verifySession($app)) {
    $perms['view'] = checkPermission($app, 'viewuser', 'perms_user');
    if ($perms['view']) {
      try {
        $statement = $app->db->prepare("SELECT authkey as id,username as user,email FROM auth WHERE approved=0 and verified=1");
        $statement->execute();
        $accounts = $statement->fetchAll();
      } catch (PDOException $ex) {
        error_log(addSlashes($ex->getMessage()) . "\r");
        $accounts = array();
      }
    } else {
      $accounts = array();
    }
    $perms['approve'] = checkPermission($app, 'approveuser', 'perms_user');
    $perms['delete'] = checkPermission($app, 'deleteuser', 'perms_user');
    $service->render("components/index.phtml", array('action' => 'user', 'page' => 'components/user.phtml', 'accounts' => $accounts, 'perms' => $perms));
  } else {
    $response->redirect("/login", 302);
  }
});

$klein->respond('GET', '/ban', function ($request, $response, $service, $app) {
  if (verifySession($app)) {
    $service->render("components/index.phtml", array('action' => 'ban', 'page' => 'components/ban.phtml'));
  } else {
    $response->redirect("/login", 302);
  }
});

$klein->respond('POST', '/login', function ( $request, $response, $service, $app) {
  $service->validateParam('email', 'Please enter a valid eamail')->isLen(5, 256);
  $service->validateParam('password', 'Please enter a password')->isLen(1, 256);
  try {
    $statement = $app->db->prepare("SELECT authkey,password,approved,verified,email FROM auth WHERE email=?");
    $statement->execute(array($request->param("email")));
    $statement->setFetchMode(PDO::FETCH_ASSOC);
    $db = $statement->fetch();
    if (!isset($db['password']) || !isset($db['authkey']) || !isset($db['approved']) || !isset($db['email'])) {
      throw new Exception("No user found");
    }
    if (password_verify($request->param('password'), $db['password'])) {
      if ($db['verified'] == 0) {
        throw new Exception("Your email has not been verified");
      }
      if ($db['approved'] == 0) {
        throw new Exception("Your account has not been approved");
      }
      $str = generate_string(64);
      $statement = $app->db->prepare("UPDATE auth SET session = ? WHERE authkey = ?");
      $statement->execute(array($str, $db['authkey']));
      $_SESSION['authkey'] = $db['authkey'];
      $_SESSION['session'] = $str;
      $service->back();
    } else {
      throw new Exception("Incorrect password");
    }
  } catch (PDOException $ex) {
    error_log(addSlashes($ex->getMessage()) . "\r");
    throw new Exception("The MySQL connection has failed, please contact the admins");
  }
});


$klein->respond('POST', '/resetpw', function($request, $response, $service, $app) {
  $service->validateParam('email', 'Invalid email')->isLen(5, 256);
  try {
    $statement = $app->db->prepare("SELECT authkey,username,email,verified FROM auth WHERE email=?");
    $statement->execute(array($request->param('email')));
    $db = $statement->fetch();
    if (!isset($db['email'])) {
      throw new Exception("No user " . $request->param('email') . " found");
    }
    if (!isset($db['verified']) || $db['verified'] == 0) {
      throw new Exception("Account " . $request->param('email') . " not verified");
    }
    $authkey = $db['authkey'];
    $resetkey = generate_string(64);
    $app->db->prepare("UPDATE auth SET data = ? WHERE authkey = ?")->execute(array($resetkey, $authkey));
    $url = $app->fullsite . '/reset-pw?authkey=' . $authkey . '&resetkey=' . $resetkey;
    $app->mail->sendMessage($app->domain, array('from' => 'Noreply <' . $app->email . '>',
        'to' => $db['email'],
        'subject' => 'Password reset for cp.ae97.net',
        'html' => 'Someone requested your password to be reset. If you wanted to do this, please use <strong><a href="' . $url . '">this link</a></strong> to '
        . 'reset your password'));
    $service->flash('Your reset link has been emailed to you');
    $response->redirect('/login', 302);
  } catch (PDOException $ex) {
    error_log(addSlashes($ex->getMessage()) . "\r");
    throw new Exception("The MySQL connection has failed, please contact the admins");
  }
});

$klein->respond('POST', '/register', function($request, $response, $service, $app) {
  $failed = false;
  try {
    $service->validateParam('username', 'Invalid username, must be 5-64 characters')->isLen(3, 64);
    $service->validateParam('email', 'Invalid email')->isLen(5, 256)->isEmail();
    $service->validateParam('email-verify', 'Invalid email')->isLen(5, 256)->isEmail();
    $service->validateParam('password', 'Invalid password, must be 5-64 characters')->isLen(5, 256);
    $service->validateParam('password-verify', 'Invalid password, must be 5-64 characters')->isLen(5, 256);
  } catch (Exception $e) {
    $service->flash('Error: ' . $e->getMessage());
    $failed = true;
  }
  if ($request->param('email') !== $request->param('email-verify')) {
    $service->flash('Error: Emails did not match');
    $failed = true;
  }
  if ($request->param('password') !== $request->param('password-verify')) {
    $service->flash('Error: Passwords did not match');
    $failed = true;
  }
  if ($failed) {
    $response->redirect("/register", 302);
  } else {
    try {
      $statement = $app->db->prepare("SELECT authkey FROM auth WHERE email=?");
      $statement->execute(array($request->param('email')));
      $result = $statement->fetch();
      if (isset($result['authkey'])) {
        $service->flash('Email already exists, please use another');
        return;
      }
      $statement = $app->db->prepare("SELECT username FROM auth WHERE username=?");
      $statement->execute(array($request->param('username')));
      $result = $statement->fetch();
      if (isset($result['user'])) {
        $service->flash('Username already exists, please use another');
        return;
      }
      $statement = $app->db->prepare('INSERT INTO auth (username,email,password,verified,approved,data) values (?,?,?,?,?,?)');
      $approveKey = generate_string(32);
      $hashedPW = password_hash($request->param('password'), PASSWORD_DEFAULT);
      $params = array($request->param('username'), $request->param('email'), $hashedPW, 0, 0, $approveKey);

      $statement->execute($params);
      $app->mail->sendMessage($app->domain, array('from' => 'Noreply <' . $app->email . '>',
          'to' => $request->param('email'),
          'subject' => 'Account approval',
          'html' => 'Someone has registered an account on <a href="' . $app->fullsite . '">' . $app->fullsite . '</a> using this email. '
          . 'If this was you, please click the following link to verify your email: <a href="' . $app->fullsite . '/verify?email=' . $request->param("email") . '&key=' . $approveKey . '">Verify email</a>'));
      $service->flash("Your account has been created, an email has been sent to verify");
      $response->redirect("/login", 302);
    } catch (PDOException $ex) {
      error_log(addSlashes($ex->getMessage()) . "\r");
      $service->flash("Error: The MySQL connection has failed, please contact the admins");
      $response->redirect('/register', 302);
    }
  }
});

$klein->respond('POST', '/user/approve/[i:id]', function($request, $response, $service, $app) {
  if (verifySession($app)) {
    try {
      $statement = $app->db->prepare("UPDATE auth SET approved=1 WHERE authkey=?");
      $statement->execute(array($request->id));
    } catch (PDOException $ex) {
      error_log(addSlashes($ex->getMessage()) . "\r");
    }
    $response->redirect("/user", 302);
  } else {
    $response->redirect("/login", 302);
  }
});

$klein->respond('POST', '/user/delete/[i:id]', function($request, $response, $service, $app) {
  if (verifySession($app)) {
    try {
      $statement = $app->db->prepare("DELETE FROM auth WHERE authkey=?");
      $statement->execute(array($request->
          id));
    } catch (PDOException $ex) {
      error_log(addSlashes($ex->getMessage()) . "\r");
    }
    $response->redirect("/user", 302);
  } else {
    $response->redirect("/login", 302);
  }
});

$klein->respond('404', function($request, $response, $service, $app) {
  $service->render("components/index.phtml", array('action' => '404', 'try' => $request));
});

$klein->onError(function ($klein, $err_msg) {
  $klein->service()->flash("Error: " . $err_msg);
  $klein->service()->back();
});

$klein->dispatch();

function verifySession($app) {
  if (!isset($_SESSION['authkey']) || !isset($_SESSION['session']) || $_SESSION['authkey'] == null || $_SESSION['session'] == null) {
    return false;
  }
  try {
    $statement = $app->db->prepare("SELECT authkey, session FROM auth WHERE authkey = ?");
    $statement->execute(array($_SESSION["authkey"]));
    $statement->setFetchMode(PDO::FETCH_ASSOC);
    $db = $statement->fetch();
    if (!isset($db['session']) || !isset($db['authkey']) || !isset($_SESSION['authkey']) || !isset($_SESSION['authkey'])) {
      $_SESSION['authkey'] = null;
      $_SESSION['session'] = null;
      return false;
    }
    if ($_SESSION['authkey'] == $db['authkey'] && $_SESSION['session'] == $db['session']) {
      return true;
    } else {
      $_SESSION['authkey'] = null;
      $_SESSION['session'] = null;
      return false;
    }
  } catch (PDOException $ex) {
    error_log(addSlashes($ex->getMessage()) . "\r");
    $_SESSION['authkey'] = null;
    $_SESSION['session'] = null;
    return false;
  }
}

function checkPermission($app, $perm, $table) {
  if (!verifySession($app)) {
    return 0;
  }
  try {
    $statement = $app->db->prepare("SELECT " . $perm . " FROM " . $table . " WHERE authkey = ?");
    $statement->execute(array($_SESSION["authkey"]));
    $statement->setFetchMode(PDO::FETCH_ASSOC);
    $db = $statement->fetch();
    return isset($db[$perm]) && $db[$perm] === "1";
  } catch (PDOException $ex) {
    error_log(addSlashes($ex->getMessage()) . "\r");
    return 0;
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

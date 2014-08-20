<?php

$this->respond('GET', '/logout', function($request, $response, $service, $app) {
    if (!verifySession($app)) {
        $response->redirect("/", 302);
    }
    try {
        $statement = $app->auth_db->prepare("DELETE FROM session WHERE userId = (SELECT userId FROM users WHERE uuid = ?)");
        $statement->execute(array($_SESSION['uuid']));
    } catch (PDOException $ex) {
        error_log(addSlashes($ex->getMessage()) . "\r");
    }
    $_SESSION['uuid'] = null;
    $_SESSION['session'] = null;
    $service->render('index.phtml', array('action' => 'logout', 'page' => 'auth/logout.phtml'));
    $response->redirect("/index", 302);
});

$this->respond('GET', '/login/[*:redirect]?', function($request, $response, $service, $app) {
    if (verifySession($app)) {
        $response->redirect("/", 302);
    }
    $service->render('index.phtml', array('action' => 'login', 'page' => 'auth/login.phtml', 'redirect' => $request->param('redirect')));
});

$this->respond('GET', '/register', function($request, $response, $service, $app) {
    if (!verifySession($app)) {
        $service->render('index.phtml', array('action' => 'register', 'page' => 'auth/register.phtml'));
    } else {
        $response->redirect("/", 302);
    }
});

$this->respond('GET', '/verify', function($request, $response, $service, $app) {
    try {
        $service->validateParam('email', 'Invalid email')->isLen(5, 256);
        $service->validateParam('key', 'Invalid verify key')->isLen(32);
        $statement = $app->auth_db->prepare("SELECT data FROM users WHERE email=?");
        $statement->execute(array($request->param("email")));
        $db = $statement->fetch();
        if ($request->param('key') == $db['data']) {
            $statement = $app->auth_db->prepare("UPDATE users SET verified = 1, data = null WHERE email=?");
            $statement->execute(array($request->param('email')));
            $service->flash('Your email has been verified');
            $response->redirect("/auth/login", 302);
        } else {
            throw new Exception("Invalid verify key for the email");
        }
    } catch (Exception $e) {
        $service->flash('Error: ' . $e->getMessage());
        $response->redirect("/auth/login", 302);
    }
});

$this->respond('GET', '/resetpw', function($request, $response, $service, $app) {
    $service->render('index.phtml', array('action' => 'resetpw', 'page' => 'auth/resetpw.phtml'));
});

$this->respond('GET', '/reset-pw', function($request, $response, $service, $app) {
    try {
        $service->validateParam('uuid', 'Invalid uuid');
        $service->validateParam('resetkey', 'Invalid reset key')->isLen(64);
    } catch (Exception $e) {
        $service->flash("Error: " . $e->getMessage());
        $response->redirect('/resetpw', 302);
    }
    try {
        $statement = $app->auth_db->prepare("SELECT uuid,data,email,verified FROM users WHERE uuid=?");
        $statement->execute(array($request->param('uuid')));
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        $db = $statement->fetch();
        if (!isset($db['data']) || !isset($db['uuid'])) {
            $service->flash("Error: No reset was requested for this account");
            $response->redirect('/resetpw', 302);
        } else if (!isset($db['verified']) || $db['verified'] == 0) {
            throw new Exception("Account not verified");
        } else if ($db['data'] == $request->param('resetkey')) {
            $unhashed = generate_string(16);
            $newpass = password_hash($unhashed, PASSWORD_DEFAULT);
            $app->auth_db->prepare("UPDATE users SET password = ?, data = null WHERE uuid = ?")->execute(array($newpass, $db['uuid']));
            $app->mail->sendMessage($app->domain, array('from' => 'Noreply@ae97.net <' . $app->email . '>',
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

$this->respond('POST', '/login/[*:redirectBack]?', function($request, $response, $service, $app) {
    $service->validateParam('email', 'Please enter a valid eamail')->isLen(5, 256);
    $service->validateParam('password', 'Please enter a password')->isLen(1, 256);
    try {
        $statement = $app->auth_db->prepare("SELECT uuid,password,approved,verified,email FROM users WHERE email=?");
        $statement->execute(array($request->param("email")));
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        $db = $statement->fetch();
        if (!isset($db['password']) || !isset($db['uuid']) || !isset($db['approved']) || !isset($db['email'])) {
            throw new Exception("No user found");
        }
        if (password_verify($request->param('password'), $db['password'])) {
            if ($db['verified'] == 0) {
                throw new Exception("Your email has not been verified");
            } else if ($db['approved'] == 0) {
                throw new Exception("Your account has not been approved");
            } else {
                $str = generate_string(64);
                $statement = $app->auth_db->prepare("INSERT INTO session (userId, sessionToken) VALUES ((SELECT users.userId FROM users WHERE uuid = ?), ?) ON DUPLICATE KEY UPDATE sessionToken = ?");
                $statement->execute(array($db['uuid'], $str, $str));
                $_SESSION['uuid'] = $db['uuid'];
                $_SESSION['session'] = $str;
                if ($request->param('redirectBack') !== null) {
                    $response->redirect('/' . $request->param('redirectBack'), 302);
                } else {
                    $response->redirect('/', 302);
                }
            }
        } else {
            throw new Exception("Incorrect password");
        }
    } catch (PDOException $ex) {
        error_log(addSlashes($ex->getMessage()) . "\r");
        throw new Exception("The MySQL connection has failed, please contact the admins");
    }
});


$this->respond('POST', '/resetpw', function($request, $response, $service, $app) {
    $service->validateParam('email', 'Invalid email')->isLen(5, 256);
    try {
        $statement = $app->auth_db->prepare("SELECT uuid,username,email,verified FROM users WHERE email=?");
        $statement->execute(array($request->param('email')));
        $db = $statement->fetch();
        if (!isset($db['email'])) {
            throw new Exception("No user " . $request->param('email') . " found");
        } else if (!isset($db['verified']) || $db['verified'] == 0) {
            throw new Exception("Account " . $request->param('email') . " not verified");
        } else {
            $uuid = $db['uuid'];
            $resetkey = generate_string(64);
            $app->auth_db->prepare("UPDATE users SET data = ? WHERE uuid = ?")->execute(array($resetkey, $uuid));
            $url = $app->fullsite . '/reset-pw?uuid=' . $uuid . '&resetkey=' . $resetkey;
            $app->mail->sendMessage($app->domain, array('from' => 'Noreply <' . $app->email . '>',
                'to' => $db['email'],
                'subject' => 'Password reset for cp.ae97.net',
                'html' => 'Someone requested your password to be reset. If you wanted to do this, please use <strong><a href="' . $url . '">this link</a></strong> to '
                . 'reset your password'));
            $service->flash('Your reset link has been emailed to you');
            $response->redirect('/login', 302);
        }
    } catch (PDOException $ex) {
        error_log(addSlashes($ex->getMessage()) . "\r");
        throw new Exception("The MySQL connection has failed, please contact the admins");
    }
});

$this->respond('POST', '/register', function($request, $response, $service, $app) {
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
        $response->redirect("/auth/register", 302);
    } else {
        try {
            $statement = $app->auth_db->prepare("SELECT uuid FROM users WHERE email=?");
            $statement->execute(array($request->param('email')));
            $result = $statement->fetch();
            if (isset($result['uuid'])) {
                $service->flash('Email already exists, please use another');
                return;
            }
            $statement = $app->auth_db->prepare("SELECT username FROM users WHERE username=?");
            $statement->execute(array($request->param('username')));
            $result = $statement->fetch();
            if (isset($result['user'])) {
                $service->flash('Username already exists, please use another');
                return;
            } else {
                $statement = $app->auth_db->prepare('INSERT INTO auth (username,email,password,verified,approved,data) values (?,?,?,?,?,?)');
                $approveKey = generate_string(32);
                $hashedPW = password_hash($request->param('password'), PASSWORD_DEFAULT);
                $params = array($request->param('username'), $request->param('email'), $hashedPW, 0, 0, $approveKey);
                $statement->execute($params);
                $app->mail->sendMessage($app->domain, array('from' => 'Noreply <' . $app->email . '>',
                    'to' => $request->param('email'),
                    'subject' => 'Account approval',
                    'html' => 'Someone has registered an account on <a href="' . $app->fullsite . '">' . $app->fullsite . '</a> using this email. '
                    . 'If this was you, please click the following link to verify your email: <a href="' . $app->fullsite . '/auth/verify?email=' . $request->param("email") . '&key=' . $approveKey . '">Verify email</a>'));
                $service->flash("Your account has been created, an email has been sent to verify");
                $response->redirect("/auth/login", 302);
            }
        } catch (PDOException $ex) {
            error_log(addSlashes($ex->getMessage()) . "\r");
            $service->flash("Error: The MySQL connection has failed, please contact the admins");
            $response->redirect('/auth/register', 302);
        }
    }
});

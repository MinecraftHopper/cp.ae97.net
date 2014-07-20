<?php

namespace Auth;

$this->respond('GET', '/logout', function($request, $response, $service, $app) {
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
    $service->render('index.phtml', array('action' => 'logout', 'page' => 'auth/logout.phtml'));
    $response->redirect("/index", 302);
});

$this->respond('GET', '/login/[*:request]?', function($request, $response, $service, $app) {
    if (verifySession($app)) {
        $response->redirect("/", 302);
    }
    $service->render('index.phtml', array('action' => 'login', 'page' => 'auth/login.phtml', 'redirect' => $request->param('request')));
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
        $statement = $app->db->prepare("SELECT data FROM auth WHERE email=?");
        $statement->execute(array($request->param("email")));
        $db = $statement->fetch();
        if ($request->param('key') == $db['data']) {
            $statement = $app->db->prepare("UPDATE auth SET verified = 1, data = null WHERE email=?");
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
            } else if ($db['approved'] == 0) {
                throw new Exception("Your account has not been approved");
            } else {
                $str = generate_string(64);
                $statement = $app->db->prepare("UPDATE auth SET session = ? WHERE authkey = ?");
                $statement->execute(array($str, $db['authkey']));
                $_SESSION['authkey'] = $db['authkey'];
                $_SESSION['session'] = $str;
                if ($request->param('redirectBack') !== null) {
                    $response->redirect('/' . $request->param('redirectBack'), 302);
                } else {
                    $service->back();
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
        $statement = $app->db->prepare("SELECT authkey,username,email,verified FROM auth WHERE email=?");
        $statement->execute(array($request->param('email')));
        $db = $statement->fetch();
        if (!isset($db['email'])) {
            throw new Exception("No user " . $request->param('email') . " found");
        } else if (!isset($db['verified']) || $db['verified'] == 0) {
            throw new Exception("Account " . $request->param('email') . " not verified");
        } else {
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
            } else {
                $statement = $app->db->prepare('INSERT INTO auth (username,email,password,verified,approved,data) values (?,?,?,?,?,?)');
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

<?php

use \AE97\Panel\Authentication,
    \AE97\Panel\Email,
    \AE97\Panel\Config,
    \AE97\Panel\User;

$this->respond('GET', '/login', function($request, $response, $service) {
    if (Authentication::verifySession()) {
        $response->redirect("/", 302);
    }
    $service->render(HTML_DIR . 'index.phtml', array('action' => 'login', 'page' => HTML_DIR . 'auth/login.phtml', 'redirect' => $request->param('redirect')));
});

$this->respond('POST', '/login', function($request, $response, $service) {
    $service->validateParam('email', 'Please enter a valid eamail')->isLen(5, 256);
    $service->validateParam('password', 'Please enter a password')->isLen(1, 256);
    $result = Authentication::validateCreds($request->param('email'), $request->param('password'));
    if (!$result) {
        $service->flash("Error: Invalid creds");
        $service->back();
        return;
    }
    if (!$result['verified']) {
        $service->flash("Your email has not been verified");
        $service->back();
        return;
    } else if (!$result['approved']) {
        $service->flash("Your account has not been approved");
        $service->back();
        return;
    } else {
        Authentication::createSession($result['uuid']);
        if ($request->param('redirect')) {
            $response->redirect($request->param('redirect'), 302);
        } else {
            $response->redirect('/', 302);
        }
    }
});

//Logout
$this->respond('GET', '/logout', function($request, $response, $service) {
    if (!Authentication::verifySession()) {
        $response->redirect("/", 302);
    }
    Authentication::clearSession();
    $service->render(HTML_DIR . 'index.phtml', array('action' => 'logout', 'page' => HTML_DIR . 'auth/logout.phtml'));
    $response->redirect("/", 302);
});

//Register
$this->respond('GET', '/register', function($request, $response, $service) {
    if (!Authentication::verifySession()) {
        $service->render(HTML_DIR . 'index.phtml', array('action' => 'register', 'page' => HTML_DIR . 'auth/register.phtml'));
    } else {
        $response->redirect("/", 302);
    }
});

$this->respond('POST', '/register', function($request, $response, $service) {
    $service->addValidator('equal', function($str, $compare) {
        return $str == $compare;
    });
    try {
        $service->validateParam('username', 'Invalid username, must be 5-64 characters')->isLen(3, 64);
        $service->validateParam('email', 'Invalid email')->isLen(5, 256)->isEmail();
        $service->validateParam('email-verify', 'Invalid email')->isLen(5, 256)->isEmail();
        $service->validateParam('password', 'Invalid password, must be 5-64 characters')->isLen(5, 256);
        $service->validateParam('password-verify', 'Invalid password, must be 5-64 characters')->isLen(5, 256);
        $service->validateParam('email', 'Emails do not match')->isEqual($request->param('email-verify'));
        $service->validateParam('password', 'Passwords do not match')->isEqual($request->param('password-verify'));
    } catch (Exception $e) {
        $service->flash('Error: ' . $e->getMessage());
        $response->redirect("/auth/register", 302);
        return;
    }
    $result = User::create($request->param('email'), $request->param('username'), $request->param('password'));
    if (!$result['success']) {
        $service->flash($result['error']);
    } else {
        Email::send($request->param('email'), 'Account verification', 'Someone has registered an account on '
                . '<a href="' . Config::getGlobal('site')['full'] . '">' . Config::getGlobal('site')['full']
                . '</a> using this email. If this was you, please click the following link to verify your email: '
                . '<a href="' . Config::getGlobal('site')['full'] . '/auth/verify?email=' . $request->param("email")
                . '&key=' . $result['verify'] . '">Verify email</a>');
        $service->flash("Your account has been created, an email has been sent to verify");
        $response->redirect("/auth/login", 302);
    }
});

//Reset
$this->respond('GET', '/resetpw', function($request, $response, $service) {
    if ($request->param('email') == null || $request->param('resetkey') == null) {
        $service->render(HTML_DIR . 'index.phtml', array('action' => 'resetpw', 'page' => HTML_DIR . 'auth/resetpw.phtml'));
    } else {
        try {
            $service->validateParam('email', 'Invalid email')->isEmail();
            $service->validateParam('resetkey', 'Invalid reset key')->isLen(64);
        } catch (Exception $e) {
            $service->flash("Error: " . $e->getMessage());
            $response->redirect('/auth/resetpw', 302);
        }
        $newPw = User::submitPasswordReset($request->param('email'), $request->param('resetkey'));
        if ($newPw != null) {
            Email::send($request->param('email'), 'New panel password', 'Your password has been changed. Your new password is : ' . $newPw);
            $service->flash('Your new password has been emailed to you');
            $response->redirect('/auth/login', 302);
        } else {
            $service->flash("Error: Reset key is not valid for the given email");
            $response->redirect('/auth/resetpw', 302);
        }
    }
});

$this->respond('POST', '/resetpw', function($request, $response, $service) {
    $service->validateParam('email', 'Invalid email')->isLen(5, 256);
    $resetKey = User::startResetPassword($request->param('email'));
    if ($resetKey == null) {
        $service->flash('Your reset link has been emailed to you');
        $response->redirect('/auth/login', 302);
        return;
    }
    $url = Config::getGlobal('site')['full'] . '/auth/resetpw?email=' . $request->param('email') . '&resetkey=' . $resetKey;
    Email::send($request->param('email'), 'Password reset for cp.ae97.net', 'Someone requested your password to be reset. If you wanted to do this, please use <strong><a href="' . $url . '">this link</a></strong> to '
            . 'reset your password');
    $service->flash('Your reset link has been emailed to you');
    $response->redirect('/auth/login', 302);
});

//Verify
$this->respond('GET', '/verify', function($request, $response, $service) {
    $service->validateParam('email', 'Invalid email')->isLen(5, 256)->isEmail();
    $service->validateParam('key', 'Invalid verify key')->isLen(32);
    if (User::verify($request->param('email'), $request->param('key'))) {
        $service->flash('Your email has been verified');
    } else {
        $service->flash('Error: Invalid verify key for the email');
    }
    $response->redirect("/auth/login", 302);
});

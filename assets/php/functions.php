<?php

function verifySession($app) {
    if (!isset($_SESSION['authkey']) || !isset($_SESSION['session']) || $_SESSION['authkey'] == null || $_SESSION['session'] == null) {
        return false;
    } else {
        try {
            $statement = $app->auth_db->prepare("SELECT authkey, session FROM users WHERE authkey = ?");
            $statement->execute(array($_SESSION["authkey"]));
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            $db = $statement->fetch();
            if (!isset($db['session']) || !isset($db['authkey'])) {
                $session ['authkey'] = null;
                $session ['session'] = null;
                return false;
            }
            if ($_SESSION['authkey'] == $db['authkey'] && $_SESSION['session'] == $db['session']) {
                return true;
            } else {
                $session['authkey'] = null;
                $session['session'] = null;
                return false;
            }
        } catch (PDOException $ex) {
            error_log(addSlashes($ex->getMessage()) . "\r");
            $_SESSION['authkey'] = null;
            $_SESSION['session'] = null;
            return false;
        }
    }
}

function checkPermission($app, $perm) {
    if (!verifySession($app)) {
        return false;
    } else {
        try {
            $statement = $app->auth_db->prepare("SELECT count(*) AS 'has' FROM permissions WHERE userId = ? AND perm IN ('*', ?)");
            $statement->execute(array($_SESSION["authkey"], $perm));
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            $db = $statement->fetch();
            return $db['has'] > 0;
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

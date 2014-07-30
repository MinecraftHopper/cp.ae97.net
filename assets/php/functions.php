<?php

function verifySession($app) {
    if (!isset($_SESSION['uuid']) || !isset($_SESSION['session']) || $_SESSION['uuid'] == null || $_SESSION['session'] == null) {
        return false;
    } else {
        try {
            $statement = $app->auth_db->prepare("SELECT uuid, session FROM users WHERE uuid = ?");
            $statement->execute(array($_SESSION["uuid"]));
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            $db = $statement->fetch();
        } catch (PDOException $ex) {
            error_log(addSlashes($ex->getMessage()) . "\r");
            $_SESSION['uuid'] = null;
            $_SESSION['session'] = null;
            return false;
        }
        if (!isset($db['session']) || !isset($db['uuid'])) {
            $_SESSION ['uuid'] = null;
            $_SESSION ['session'] = null;
            return false;
        }
        if ($_SESSION['uuid'] == $db['uuid'] && $_SESSION['session'] == $db['session']) {
            return true;
        } else {
            $_SESSION['uuid'] = null;
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
            $statement = $app->auth_db->prepare("SELECT count(*) AS 'has' FROM perms_user WHERE userId = ? AND perm IN ('*', ?)");
            $statement->execute(array($_SESSION["uuid"], $perm));
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

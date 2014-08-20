<?php

function verifySession($app) {
    if (!isset($_SESSION['uuid']) || !isset($_SESSION['session']) || $_SESSION['uuid'] == null || $_SESSION['session'] == null) {
        return false;
    }
    try {
        $statement = $app->auth_db->prepare("SELECT sessionToken FROM session
                                            INNER JOIN users ON users.userId = session.userId
                                            WHERE users.uuid = ?");
        $statement->execute(array($_SESSION["uuid"]));
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        $db = $statement->fetch();
    } catch (PDOException $ex) {
        error_log(addSlashes($ex->getMessage()) . "\r");
        $_SESSION['uuid'] = null;
        $_SESSION['session'] = null;
        return false;
    }
    if (!isset($db['sessionToken'])) {
        $_SESSION ['uuid'] = null;
        $_SESSION ['session'] = null;
        return false;
    }
    if ($_SESSION['session'] == $db['sessionToken']) {
        return true;
    } else {
        $_SESSION['uuid'] = null;
        $_SESSION['session'] = null;
        return false;
    }
}

function checkPermission($app, $perm) {
    if (!verifySession($app)) {
        return false;
    } else {
        try {
            $statement = $app->auth_db->prepare(
                    "SELECT count(*) AS 'has'
FROM groupperms
INNER JOIN permissions ON permissions.permId = groupperms.permission
INNER JOIN groups ON groups.groupId = groupperms.groupId
WHERE groupperms.groupId IN (
  SELECT groupId FROM usergroups INNER JOIN users ON users.userId = usergroups.userId WHERE uuid = ?
)
AND permissions.perm IN ('*', ?)");
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

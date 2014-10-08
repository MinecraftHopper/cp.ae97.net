<?php

function verifySession($app) {
    if (!isset($_SESSION['uuid']) || !isset($_SESSION['session']) || $_SESSION['uuid'] == null || $_SESSION['session'] == null) {
        return false;
    }
    try {
        $statement = $app->auth_db->prepare("SELECT sessionToken FROM session
                                            WHERE uuid = ?");
        $statement->execute(array($_SESSION["uuid"]));
        $db = $statement->fetch();
    } catch (PDOException $ex) {
        logError($ex);
        clearSession();
        return false;
    }
    if (!isset($db['sessionToken']) || $_SESSION['session'] !== $db['sessionToken']) {
        clearSession();
        return false;
    } else {
        return true;
    }
}

function checkPermission($app, $perm) {
    try {
        $statement = $app->auth_db->prepare(
                "SELECT count(*) AS 'has'
FROM groupperms
INNER JOIN groups ON groups.groupId = groupperms.groupId
WHERE groupperms.groupId IN (
  SELECT groupId FROM usergroups WHERE useruuid = ?
)
AND permission IN ('*', ?)");
        $statement->execute(array($_SESSION["uuid"], $perm));
        $db = $statement->fetch();
        return $db['has'] > 0;
    } catch (PDOException $ex) {
        logError($ex);
        return false;
    }
}

function generate_string($length) {
    $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $str = '';
    $count = strlen($charset);
    for ($i = 0; $i < $length; $i++) {
        $str .= $charset[mt_rand(0, $count - 1)];
    }
    return $str;
}

function clearSession() {
    $_SESSION['uuid'] = null;
    $_SESSION['session'] = null;
}

function logError($ex) {
    error_log(addSlashes($ex instanceof Exception ? $ex->getMessage() : $ex) . "\r");
}

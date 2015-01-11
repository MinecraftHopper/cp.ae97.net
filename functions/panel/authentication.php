<?php

namespace AE97\Panel;

class Authentication {

    public static function verifySession($app) {
        if (!isset($_SESSION['uuid']) || !isset($_SESSION['session']) || $_SESSION['uuid'] == null || $_SESSION['session'] == null) {
            return false;
        }
        try {
            $statement = $app->auth_db->prepare("SELECT sessionToken FROM session WHERE uuid = ?");
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

    public static function checkPermission($app, $perm) {
        try {
            $statement = $app->auth_db->prepare(
                  "SELECT count(*) AS 'has'
                    FROM userperms
                    WHERE userId = ?
                    AND permission IN ('*', ?)"
            );
            $statement->execute(array($_SESSION["uuid"], $perm));
            $db = $statement->fetch();
            return $db['has'] > 0;
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return false;
        }
    }

    public static function clearSession() {
        $_SESSION['uuid'] = null;
        $_SESSION['session'] = null;
    }

}

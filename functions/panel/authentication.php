<?php

namespace AE97\Panel;

use \AE97\Panel\Utilities,
    \PDO;

class Authentication {

    private static $database;

    public static function verifySession() {
        if (!isset($_SESSION['uuid']) || !isset($_SESSION['session']) || $_SESSION['uuid'] == null || $_SESSION['session'] == null) {
            return false;
        }
        self::validateDatabase();
        try {
            $statement = self::$database->prepare("SELECT sessionToken FROM session WHERE uuid = ?");
            $statement->execute(array($_SESSION["uuid"]));
            $db = $statement->fetch();
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            self::clearSession();
            return false;
        }
        if (!isset($db['sessionToken']) || $_SESSION['session'] !== $db['sessionToken']) {
            self::clearSession();
            return false;
        } else {
            return true;
        }
    }

    public static function checkPermission($perm) {
        self::validateDatabase();
        try {
            $statement = self::$database->prepare(
                    "SELECT count(*) AS 'has'
                    FROM userperms
                    INNER JOIN `permissions` ON userperms.permission = permissions.id
                    WHERE userid = ? AND perm IN ('*', ?)"
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
        self::validateDatabase();
        $statement = self::$database->prepare("DELETE FROM session WHERE uuid = ?");
        $statement->execute(array($_SESSION['uuid']));
        $_SESSION['uuid'] = null;
        $_SESSION['session'] = null;
    }

    public static function validateCreds($email, $password) {
        self::validateDatabase();
        $statement = self::$database->prepare("SELECT uuid,password,approved,verified,email FROM users WHERE email=?");
        $statement->execute(array($email));
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        $db = $statement->fetch();
        if (!isset($db['password']) || !isset($db['uuid']) || !isset($db['approved']) || !isset($db['email']) || !password_verify($password, $db['password'])) {
            return false;
        } else {
            return array('uuid' => $db['uuid'], 'approved' => $db['approved'] ? true : false, 'verified' => $db['verified'] ? true : false);
        }
    }

    public static function createSession($uuid) {
        self::validateDatabase();
        $str = Utilities::generate_string(64);
        $statement = self::$database->prepare("INSERT INTO session (uuid, sessionToken) VALUES (?, ?) ON DUPLICATE KEY UPDATE sessionToken = ?");
        $statement->execute(array($uuid, $str, $str));
        $_SESSION['uuid'] = $uuid;
        $_SESSION['session'] = $str;
    }

    private static function validateDatabase() {
        if (self::$database == null) {
            $_DATABASE = Config::getGlobal('database');
            self::$database = new PDO("mysql:host=" . $_DATABASE['host'] . ";dbname=" . $_DATABASE['authdb'], $_DATABASE['user'], $_DATABASE['pass'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
            self::$database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

}

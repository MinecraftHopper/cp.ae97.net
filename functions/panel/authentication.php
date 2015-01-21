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

    public static function createUser($email, $username, $password) {
        $statement = self::$database->prepare("SELECT uuid,username FROM users WHERE email=? OR username=?");
        $statement->execute(array($email, $username));
        $result = $statement->fetch();
        if (isset($result['uuid'])) {
            return array('success' => false, 'verify' => 'Email already exists, please use another');
        }
        if (isset($result['username'])) {
            return array('success' => false, 'verify' => 'Username already exists, please use another');
        } else {
            $createUserStatement = self::$database->prepare('INSERT INTO users (uuid,username,email,password,verified,approved) values (?,?,?,?,?,?)');
            $hashedPW = password_hash($password, PASSWORD_DEFAULT);
            $createUserStatement->execute(array(Utilities::generateGUID(), $username, $email, $hashedPW, 0, 0));

            $verificationStatement = self::$database->prepare('INSERT INTO verification (email, code) VALUES (?, ?)');
            $approveKey = Utilities::generate_string(32);
            $verificationStatement->execute(array($email, $approveKey));
        }
        return array('success' => true, 'verify' => $approveKey);
    }

    public static function startResetPassword($email) {
        
    }

    public static function verifyUser($email, $key) {
        $statement = self::$database->prepare("SELECT code FROM verification WHERE email=?");
        $statement->execute(array($email));
        $db = $statement->fetch();
        if ($key == $db['code']) {
            $statement = self::$database->prepare("UPDATE users SET verified = 1 WHERE email=?");
            $statement->execute(array($email));
            return true;
        } else {
            return false;
        }
    }

    private static function validateDatabase() {
        if (self::$database == null) {
            $_DATABASE = Config::getGlobal('database');
            self::$database = new PDO("mysql:host=" . $_DATABASE['host'] . ";dbname=" . $_DATABASE['authdb'], $_DATABASE['user'], $_DATABASE['pass'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
            self::$database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

}

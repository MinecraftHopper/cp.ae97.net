<?php

namespace AE97\Panel;

use \PDO,
    \PDOException;

class User {

    private static $database;

    public static function startResetPassword($email) {
        self::validateDatabase();
        $statement = self::$database->prepare("SELECT email FROM users WHERE email=?");
        $statement->execute(array($email));
        $db = $statement->fetch();
        if (!isset($db['email'])) {
            return null;
        }
        $resetkey = Utilities::generate_string(64);
        $check = self::$database->prepare("SELECT count(*) AS count FROM passwordreset WHERE email = ?");
        $check->execute(array($email));
        if ($check->fetch()['has'] > 0) {
            self::$database->prepare("UPDATE passwordreset SET resetkey = ? WHERE email = ?")->execute(array($resetkey, $email));
        } else {
            self::$database->prepare("INSERT INTO passwordreset VALUES (?, ?)")->execute(array($email, $resetkey));
        }
        return $resetkey;
    }

    public static function submitPasswordReset($email, $key) {
        self::validateDatabase();
        $statement = self::$database->prepare("SELECT resetkey FROM passwordreset WHERE email=?");
        $statement->execute(array($email));
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        $db = $statement->fetch();
        if (!isset($db['resetkey'])) {
            return null;
        } else if ($db['resetkey'] == $key) {
            $unhashed = Utilities::generate_string(16);
            $newpass = password_hash($unhashed, PASSWORD_DEFAULT);
            self::$database->prepare("UPDATE users SET password = ? WHERE email = ?")->execute(array($newpass, $email));
            return $unhashed;
        } else {
            return null;
        }
    }

    public static function verify($email, $key) {
        self::validateDatabase();
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

    public static function approve($id) {
        self::validateDatabase();
        $statement = self::$database->prepare("UPDATE users SET approved=1 WHERE uuid=?");
        $statement->execute(array($id));
    }

    public static function create($email, $username, $password) {
        self::validateDatabase();
        $statement = self::$database->prepare("SELECT uuid,username FROM users WHERE email=? OR username=?");
        $statement->execute(array($email, $username));
        $result = $statement->fetch();
        if (isset($result['uuid'])) {
            return array('success' => false, 'error' => 'Email already exists, please use another');
        }
        if (isset($result['username'])) {
            return array('success' => false, 'error' => 'Username already exists, please use another');
        } else {
            $createUserStatement = self::$database->prepare('INSERT INTO users (uuid,username,email,password,verified,approved) values (?,?,?,?,?,?)');
            $hashedPW = password_hash($password, PASSWORD_DEFAULT);
            $createUserStatement->execute(array(Utilities::generateGUID(), $username, $email, $hashedPW, 0, 1));

            $verificationStatement = self::$database->prepare('INSERT INTO verification (email, code) VALUES (?, ?)');
            $approveKey = Utilities::generate_string(32);
            $verificationStatement->execute(array($email, $approveKey));
        }
        return array('success' => true, 'verify' => $approveKey);
    }

    public static function getUnapproved() {
        self::validateDatabase();
        $statement = self::$database->prepare("SELECT uuid as id,username as user,email FROM users WHERE approved=0 and verified=1");
        $statement->execute();
        return $statement->fetchAll();
    }

    public static function editPerms($username, $perms) {
        self::validateDatabase();
        $database = self::$database;
        try {
            $useridStmt = $database->prepare("SELECT uuid FROM users WHERE username = ?");
            $useridStmt->execute(array($username));
            $user = $useridStmt->fetch();
            if (!isset($user['uuid'])) {
                return;
            }
            $uuid = $user['uuid'];

            $database->beginTransaction();
            $database->prepare("DELETE FROM userperms WHERE userId = ?")->execute(array($uuid));
            if ($perms != null) {
                foreach ($perms as $perm) {
                    $database->prepare("INSERT INTO userperms VALUES(?,(SELECT id FROM permissions WHERE perm = ?))")->execute(array($uuid, $perm));
                }
            }
            $database->commit();
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            try {
                $database->rollBack();
            } catch (Exception $e) {
                Utilities::logError($e);
            }
        }
    }

    public static function get($name) {
        self::validateDatabase();
        $userstmt = self::$database->prepare("SELECT uuid, username, email, verified, approved, nickserv FROM users WHERE username = ?");
        $userstmt->execute(array($name));
        return $userstmt->fetch();
    }

    public static function getByUUID($name) {
        self::validateDatabase();
        $userstmt = self::$database->prepare("SELECT uuid, username, email, verified, approved, nickserv FROM users WHERE uuid = ?");
        $userstmt->execute(array($name));
        return $userstmt->fetch();
    }

    public static function getPerms($uuid = null) {
        if ($uuid == null) {
            $permstmt = self::$database->prepare("SELECT perm FROM permissions");
            $permstmt->execute();
            return $permstmt->fetchAll();
        } else {
            $userpermstmt = self::$database->prepare("SELECT perm FROM permissions INNER JOIN userperms ON userperms.permission = permissions.id WHERE userId = ?");
            $userpermstmt->execute(array($uuid));
            return $userpermstmt->fetchAll();
        }
    }

    public static function getAll() {
        self::validateDatabase();
        $users = self::$database->prepare("SELECT `uuid` AS `id`,`username` FROM users");
        $users->execute();
        return $users->fetchAll();
    }

    public static function changePassword($uuid, $newpw) {
        self::validateDatabase();
        self::$database->prepare("UPDATE users SET password = ? WHERE uuid = ?")->execute(array(password_hash($newpw, PASSWORD_DEFAULT), $uuid));
    }

    public static function changeNickserv($uuid, $nickserv) {
        self::validateDatabase();
        self::$database->prepare("UPDATE users SET nickserv = ? WHERE uuid = ?")->execute(array($nickserv, $uuid));
    }

    private static function validateDatabase() {
        if (self::$database == null) {
            $_DATABASE = Config::getGlobal('database');
            self::$database = new PDO("mysql:host=" . $_DATABASE['host'] . ";dbname=" . $_DATABASE['authdb'], $_DATABASE['user'], $_DATABASE['pass'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
            self::$database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

}

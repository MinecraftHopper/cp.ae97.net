<?php

namespace AE97\Panel;

use \PDO,
    \PDOException;

class HJT {

    private static $database;

    public static function getName($name) {
        self::validateDatabase();
        try {
            $statement = self::$database->prepare("SELECT name, value "
                    . "FROM hjt "
                    . "WHERE name = ?");
            $statement->execute(array($name));
            $result = $statement->fetchAll();
            return count($result) >= 1 ? $result[0] : null;
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return null;
        }
    }

    public static function getHJTs($page = 1) {
        if ($page == null) {
            $page = 1;
        }
        self::validateDatabase();
        try {
            $query = "SELECT name, value "
                    . "FROM hjt ";
            $statement = self::$database->prepare($query);
            $statement->execute();
            $record = $statement->fetchAll(PDO::FETCH_ASSOC);
            return $record;
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return array();
        }
    }

    public static function addHJT($name, $value) {
        self::validateDatabase();
        try {
            $statement = self::$database->prepare("INSERT INTO hjt (name, value) VALUES (?,?)");
            $statement->execute(array(trim($name), trim($value)));
            return self::$database->lastInsertId();
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return false;
        }
    }

    public static function removeHJT($name) {
        self::validateDatabase();
        try {
            self::$database->prepare("DELETE FROM hjt WHERE name = ?")->execute(array($name));
            return true;
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return false;
        }
    }

    public static function updateHJT($name, $value) {
        self::validateDatabase();
        try {
            self::$database->prepare("UPPDATE hjt SET value = ? WHERE name = ?")->execute(array($value, $name));
            return true;
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return false;
        }
    }

    private static function validateDatabase() {
        if (self::$database == null) {
            $_DATABASE = Config::getGlobal('database')['hjt'];
            self::$database = new PDO("mysql:host=" . $_DATABASE['host'] . ";dbname=" . $_DATABASE['authdb'], $_DATABASE['user'], $_DATABASE['pass'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
            self::$database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

}

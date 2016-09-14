<?php
namespace AE97\Panel;
use \PDO,
    \PDOException;
class Bans {
    private static $database;
    private static $hjtPerPage = 20;

    public static function getName($name) {
        self::validateDatabase();
        try {
            $statement = self::$database->prepare("SELECT name, value "
                    . "FROM hjt "
                    . "WHERE name = ?");
            $statement->execute(array($name));
            $result = self::combineChans($statement->fetchAll());
            return count($result) >= 1 ? $result[$id] : null;
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
                    . "FROM hjt "
                    . "LIMIT " . strval(intval($page - 1) * self::$hjtPerPage) . ", " . self::$hjtPerPage);
            $statement = self::$database->prepare($query);
            $statement->execute();
            $record = $statement->fetchAll(PDO::FETCH_ASSOC);
            return self::combineChans($record);
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return array();
        }
    }
    public static function getHJTPages() {
        self::validateDatabase();
        try {
            $statement = self::$database->prepare("SELECT count(*) AS count "
                    . "FROM hjt "
            );
            $statement->execute();
            $record = $statement->fetch(PDO::FETCH_ASSOC);
            return ceil($record['count'] / self::$bansPerPage);
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
  
    private static function validateDatabase() {
        if (self::$database == null) {
            $_DATABASE = Config::getGlobal('database')['hjt'];
            self::$database = new PDO("mysql:host=" . $_DATABASE['host'] . ";dbname=" . $_DATABASE['authdb'], $_DATABASE['user'], $_DATABASE['pass'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
            self::$database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);            
        }
    }
}

<?php

namespace AE97\Panel;

use \PDO;

class Bot {

    public static function getConfig() {
        $database = self::openConnection();
        $statement = $database->prepare('SELECT `id`, val FROM config WHERE `id` NOT LIKE "global.%"');
        $statement->execute();
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function set($key, $value) {
        $database = self::openConnection();
        $statement = $database->prepare('UPDATE config SET val = ? WHERE `id` = ?');
        $statement->execute(array($value, $key));
    }

    private static function openConnection() {
        $_DATABASE = Config::getGlobal('database');
        $database = new PDO("mysql:host=" . $_DATABASE['host'] . ";dbname=" . $_DATABASE['db'], $_DATABASE['user'], $_DATABASE['pass'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
        $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $database;
    }

}

<?php

namespace AE97\Panel;

use \PDO;

class PmcEmail {

    public static function getCodes() {
        $database = self::openConnection();
        $codeListStmt = $database->prepare("SELECT id,email,ticket,code FROM pmcemail ORDER BY ID DESC LIMIT 25");
        $codeListStmt->execute();
        return $codeListStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function deleteCode($id) {
        $database = self::openConnection();
        $codeListStmt = $database->prepare("DELETE FROM pmcemail WHERE id = ?");
        $codeListStmt->execute(array($id));
    }

    public static function addCode($email, $ticketId) {
        $database = self::openConnection();
        $insert = $database->prepare("INSERT INTO pmcemail VALUES (?, ?, ?, ?)");
        $code = PmcEmail::generateCode();
        $insert->execute(array(0, $email, $ticketId, $code));
        return $code;
    }

    private static function openConnection() {
        $_DATABASE = Config::getGlobal('database')['factoid'];
        $database = new PDO("mysql:host=" . $_DATABASE['host'] . ";dbname=" . $_DATABASE['factoiddb'], $_DATABASE['user'], $_DATABASE['pass'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
        $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $database;
    }

    private static function generateCode($length = 10) {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

}

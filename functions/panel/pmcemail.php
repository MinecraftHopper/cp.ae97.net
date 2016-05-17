<?php

namespace AE97\Panel;

use \PDO;

class PmcEmail {

    public static function getCodes() {
        $database = self::openConnection();
        $codeListStmt = $database->prepare("SELECT id,email,ticket,code FROM pmcemail WHERE deleted = 0 ORDER BY ID DESC LIMIT 25");
        $codeListStmt->execute();
        return $codeListStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function deleteCode($id, $user) {
        $database = self::openConnection();
        $codeListStmt = $database->prepare("UPDATE pmcemail SET deleted = 1, delete_user = ? WHERE id = ?");
        $codeListStmt->execute(array($user, $id));
    }

    public static function addCode($email, $ticketId, $user) {
        $database = self::openConnection();
        $insert = $database->prepare("INSERT INTO pmcemail (email, ticket, code, create_user) VALUES (?, ?, ?, ?)");
        $code = PmcEmail::generateCode();
        $insert->execute(array($email, $ticketId, $code, $user));
        return $code;
    }

    private static function openConnection() {
        $_DATABASE = Config::getGlobal('database')['factoid'];
        $database = new PDO("mysql:host=" . $_DATABASE['host'] . ";dbname=" . $_DATABASE['factoiddb'], $_DATABASE['user'], $_DATABASE['pass'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
        $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $database;
    }

    private static function generateCode($length = 8) {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

}

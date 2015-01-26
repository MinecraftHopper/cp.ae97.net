<?php

namespace AE97\Panel;

use \PDO,
    \PDOException;

class Bans {

    private static $database;

    public static function getBan($id) {
        self::validateDatabase();
        try {
            $statement = self::$database->prepare("SELECT id, issuedBy, kickMessage, issueDate, channel, type "
                  . "FROM bans "
                  . "INNER JOIN banchannels ON bans.id = banId "
                  . "WHERE id = ?");
            $statement->execute(array($id));
            return self::combineChans($statement->fetch());
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return array();
        }
    }

    public static function getBans($page = 1) {
        self::validateDatabase();
        try {
            $statement = self::$database->prepare("SELECT id, users.username, content, kickMessage, issueDate, expireDate, channel, type, notes "
                  . "FROM bans "
                  . "INNER JOIN banchannels ON bans.id = banId "
                  . "INNER JOIN users ON users.uuid = issuedBy "
                  . "ORDER BY id "
                  //. "LIMIT " . strval(intval($page) * 10) . ", 10"
                  );
            $statement->execute();
            $record = $statement->fetchAll(PDO::FETCH_ASSOC);
            return self::combineChans($record);
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return array();
        }
    }

    public static function addBan($mask, $issuer, $kickMessage, $expireDate, $notes = "No private notes") {
        self::validateDatabase();
        try {
            $statement = self::$database->prepare("INSERT INTO bans (type, content, issuedBy, kickMessage, notes, expireDate) VALUES (?,?,?,?,?,?)");
            $statement->execute(array(0, $mask, $issuer, $kickMessage, $notes, $expireDate));
            return true;
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return false;
        }
    }

    public static function removeBan($id) {
        self::validateDatabase();
        try {
            self::$database->prepare("DELETE FROM banchannels WHERE banId = ?")->execute(array($id));
            self::$database->prepare("DELETE FROM bans WHERE id = ?")->execute(array($id));
            return true;
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return false;
        }
    }

    public static function addChannelToBan($banId, $channel) {
        self::validateDatabase();
        try {
            self::$database->prepare("INSERT INTO banchannels VALUES(?,?)")->execute(array($banId, $channel));
            return true;
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return false;
        }
    }

    public static function removeChannelFromBan($banId, $channel) {
        self::validateDatabase();
        try {
            self::$database->prepare("DELETE FROM banchannels WHERE banId = ? AND channel = ?")->execute(array($banId, $channel));
            return true;
        } catch (PDOException $ex) {
            Utilities::logError($ex);
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

    private static function combineChans($record) {
        $casted = array();
        foreach ($record as $ban) {
            if (!isset($casted[$ban['id']])) {
                $casted[$ban['id']] = array(
                    'id' => $ban['id'],
                    'issuer' => $ban['username'],
                    'kickmessage' => $ban['kickMessage'],
                    'issueDate' => $ban['issueDate'],
                    'expireDate' => $ban['expireDate'],
                    'type' => $ban['type'] === 0 ? "standard" : "extended",
                    'channels' => array($ban['channel']),
                    'content' => $ban['content'],
                    'notes' => $ban['notes']
                );
            } else {
                $casted[$ban['id']]['channels'][] = $ban['channel'];
            }
        }
        return $casted;
    }

}

<?php

namespace AE97\Panel;

use \PDOException;

class Bans {

    private $database;

    public function __construct($database) {
        $this->database = $database;
    }

    public function getBan($id = null) {
        try {
            if ($id == null) {
                $statement = $this->database->prepare('SELECT id, type, content AS mask, issuedBy AS issuer, kickMessage AS message, notes, issueDate, expireDate');
                $statement->execute();
                return $statement->fetchAll();
            } else {
                $statement = $this->database->prepare('SELECT id, type, content AS mask, issuedBy AS issuer, kickMessage AS message, notes, issueDate, expireDate'
                        . ' WHERE id = ?');
                $statement->execute(array($id));
                return $statement->fetch();
            }
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return array();
        }
    }

    public function addBan($mask, $issuer, $kickMessage, $expireDate, $notes = "No private notes") {
        try {
            $statement = $this->database->prepare("INSERT INTO bans (type, content, issuedBy, kickMessage, notes, expireDate) VALUES (?,?,?,?,?,?)");
            $statement->execute(array(0, $mask, $issuer, $kickMessage, $notes, $expireDate));
            return true;
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return false;
        }
    }

    public function removeBan($id) {
        try {
            $this->database->prepare("DELETE FROM banchannels WHERE banId = ?")->execute(array($id));
            $this->database->prepare("DELETE FROM bans WHERE id = ?")->execute(array($id));
            return true;
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return false;
        }
    }

    public function addChannelToBan($banId, $channel) {
        try {
            $this->database->prepare("INSERT INTO banchannels VALUES(?,?)")->execute(array($banId, $channel));
            return true;
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return false;
        }
    }

    public function removeChannelFromBan($banId, $channel) {
        try {
            $this->database->prepare("DELETE FROM banchannels WHERE banId = ? AND channel = ?")->execute(array($banId, $channel));
            return true;
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return false;
        }
    }

}

<?php
namespace AE97\Panel;

class Bans {

    private $database;

    public function __construct($database) {
        $this->database = $database;
    }

    public function getBan($id = null) {
        if($id == null) {
            $statement = $this->database->prepare('SELECT id, type, content AS mask, issuedBy AS issuer, kickMessage AS message, notes, issueDate, expireDate');
            $statement->execute();
            return $statement->fetchAll();
        } else {
            $statement = $this->database->prepare('SELECT id, type, content AS mask, issuedBy AS issuer, kickMessage AS message, notes, issueDate, expireDate'
                  . ' WHERE id = ?');
            $statement->execute(array($id));
            return $statement->fetch();
        }
    }

    public function addBan($mask, $issuer, $kickMessage, $expireDate, $notes = "No private notes") {
        $statement = $this->database->prepare("INSERT INTO bans (type, content, issuedBy, kickMessage, notes, expireDate) VALUES (?,?,?,?,?,?)");
        $statement->execute(array(0, $mask, $issuer, $kickMessage, $notes, $expireDate));
    }

    public function removeBan($id) {
        $this->database->prepare("DELETE FROM banchannels WHERE banId = ?")->execute(array($id));
        $this->database->prepare("DELETE FROM bans WHERE id = ?")->execute(array($id));
    }

    public function addChannelToBan($banId, $channel) {
        $this->database->prepare("INSERT INTO banchannels VALUES(?,?)")->execute(array($banId, $channel));
    }

    public function removeChannelFromBan($banId, $channel) {
        $this->database->prepare("DELETE FROM banchannels WHERE banId = ? AND channel = ?")->execute(array($banId, $channel));
    }

}
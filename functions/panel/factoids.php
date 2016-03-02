<?php

namespace AE97\Panel;

use \stdClass,
    \AE97\Validate,
    \PDOException,
    \PDO;

class Factoids {

    public static function getDatabase($table = 'global') {
        $database = self::openConnection();
        try {
            $gameliststatement = $database->prepare("SELECT id,idname,displayname FROM games");
            $gameliststatement->execute();
            $gamelist = $gameliststatement->fetchAll();
            $statement = $database->prepare("SELECT factoids.id,factoids.name, factoids.content, games.displayname "
                    . "FROM factoids "
                    . "INNER JOIN games ON (factoids.game = games.id) "
                    . "WHERE games.idname = ?");
            $statement->execute(array(0 => $table));
            $factoids = $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return array();
        }

        //TODO: Rewrite this logic, it can be made more effective
        $firstCounter = 0;
        foreach ($gamelist as $gameitem):
            $compiledGamelist[$firstCounter] = array('idname' => $gameitem['idname'], 'displayname' => $gameitem['displayname']);
            if ($compiledGamelist[$firstCounter]['idname'] === $table) {
                $gameAskedFor = $compiledGamelist[$firstCounter];
            }
            $firstCounter++;
        endforeach;

        $compiledFactoidlist = array();
        foreach ($factoids as $f):
            $compiledFactoidlist[] = array('id' => $f['id'], 'name' => $f['name'], 'content' => $f['content'], 'game' => $table == null ? $f['game'] : $table);
        endforeach;

        $collection = array();
        $collection['gamerequest'] = isset($gameAskedFor) ? $gameAskedFor : 'Global';
        $collection['games'] = isset($compiledGamelist) ? $compiledGamelist : array();
        $collection['factoids'] = $compiledFactoidlist;
        return $collection;
    }

    public static function getFactoid($id) {
        $database = self::openConnection();
        try {
            $statement = $database->prepare("SELECT factoids.id AS id,name,content,games.displayname AS game "
                    . "FROM factoids "
                    . "INNER JOIN games ON factoids.game = games.id "
                    . "WHERE factoids.id=? "
                    . "LIMIT 1");
            $statement->execute(array($id));
            return $statement->fetch();
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return null;
        }
    }

    public static function getGame($id = null) {
        $database = self::openConnection();
        try {
            if ($id != null) {
                Validate::param($id)->isNum();

                $statement = $database->prepare("SELECT idname AS id,displayname AS name FROM games INNER JOIN factoids ON factoids.game = games.id WHERE factoids.id = ?");
                return $statement->execute(array($id));
            } else {
                $statement = $database->prepare("SELECT idname AS id,displayname AS name FROM games");
                return $statement->execute();
            }
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return array();
        }
    }

    public static function getDatabaseNames() {
        try {
            $statement = self::openConnection()->prepare("SELECT idname, displayname FROM games");
            $statement->execute();
            return $statement->fetchAll();
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            return array();
        }
    }

    public static function editFactoid($id, $content) {
        Validate::param($id, 'id')->isNum();
        Validate::param($content, 'content')->notNull();
        $database = self::openConnection();
        try {
            $database->beginTransaction();

            $getOld = $database->prepare("SELECT content FROM factoids WHERE id = ?");
            $getOld->execute(array($id));
            $old = $getOld->fetch()['content'];

            $statement = $database->prepare("UPDATE factoids SET content = ? WHERE id = ?");
            $statement->execute(array($content, $id));

            $anonObj = new stdClass();
            $anonObj->old = $old;
            $anonObj->new = $content;
            self::updateLogs($database, 'edit', $id, $anonObj);
            $database->commit();
            return $statement->rowCount() > 0;
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            $database->rollBack();
            return false;
        }
    }

    public static function deleteFactoid($id) {
        Validate::param($id, 'id')->isNum();
        $database = self::openConnection();
        try {
            $database->beginTransaction();

            $select = $database->prepare("SELECT name, game, content FROM factoids WHERE id = ?");
            $select->execute(array($id));
            $content = $select->fetch();

            $statement = $database->prepare("DELETE FROM factoids WHERE id = ?");
            $statement->execute(array($id));

            self::updateLogs($database, 'delete', $id, $content);
            $database->commit();
            return $statement->rowCount() > 0;
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            $database->rollBack();
            return false;
        }
    }

    public static function createFactoid($table, $key, $content) {
        Validate::param($table)->notNull();
        Validate::param($key)->notNull();
        Validate::param($content)->notNull();
        $database = self::openConnection();
        try {
            $database->beginTransaction();

            $statement = $database->prepare("INSERT INTO factoids (`name`,`game`,`content`) VALUES (?,(SELECT id FROM games WHERE idname = ?),?)");
            $statement->execute(array($key, $table, $content));

            $select = $database->prepare("SELECT id FROM factoids WHERE name = ? AND game = ?");
            $select->execute(array($key, $table));
            $id = $select->fetch()['id'];

            $anonObj = new stdClass();
            $anonObj->content = $content;
            $anonObj->game = $table;
            self::updateLogs($database, 'create', $id, $anonObj);
            $database->commit();
            return $statement->rowCount() > 0;
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            $database->rollBack();
            return false;
        }
    }

    public static function createDatabase($idName, $displayName = null) {
        Validate::param($idName)->notNull();
        if ($displayName == null) {
            $displayName = $idName;
        }

        $database = self::openConnection();
        try {
            $statement = $database->prepare("INSERT INTO games (`idname`,`displayname`) VALUES (?,?)");
            $statement->execute(array($idName, $displayName));
            return true;
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            $database->rollBack();
            return false;
        }
    }

    public static function renameFactoid($id, $newName) {
        Validate::param($id, 'id')->isNum();
        Validate::param($newName, 'name')->notNull();
        $database = self::openConnection();
        try {
            $database->beginTransaction();

            $select = $database->prepare("SELECT name FROM factoids WHERE id = ?");
            $select->execute(array($id));
            $oldName = $select->fetch()['name'];

            $statement = $database->prepare("UPDATE factoids SET name = ? WHERE id = ?");
            $statement->execute(array($newName, $id));

            $anonObj = new stdClass();
            $anonObj->oldName = $oldName;
            $anonObj->newName = $newName;

            self::updateLogs($database, 'rename', $id, $anonObj);
            $database->commit();
            return $statement->rowCount() > 0;
        } catch (PDOException $ex) {
            Utilities::logError($ex);
            $database->rollBack();
            return false;
        }
    }

    /**
     * @return PDO database
     */
    private static function openConnection() {
        $_DATABASE = Config::getGlobal('database')['factoid'];
        $database = new PDO("mysql:host=" . $_DATABASE['host'] . ";dbname=" . $_DATABASE['factoiddb'], $_DATABASE['user'], $_DATABASE['pass'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
        $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $database;
    }

    private static function updateLogs(PDO $database, $action, $id, $data) {
        $database->prepare("INSERT INTO factoid_logs (user, action, factoidid, data) VALUES (?, ?, ?, ?)")->execute(array(
            $_SESSION['uuid'],
            $action,
            $id,
            $data == null ? NULL : json_encode($data)
        ));
    }

}

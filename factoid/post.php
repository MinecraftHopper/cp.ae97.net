<?php

function getDatabase($database, $game) {
  try {
    $gameliststatement = $database->prepare("SELECT idname,displayname FROM games");
    $gameliststatement->execute();
    $gamelist = $gameliststatement->fetchAll();
    if ($game == null) {
      $statement = $database->prepare("SELECT id,name,content,game FROM factoids");
      $statement->execute();
    } else {
      $statement = $database->prepare("SELECT id,name,content FROM factoids WHERE game=?");
      $statement->execute(array($game));
    }
    $factoids = $statement->fetchAll();
  } catch (PDOException $ex) {
    error_log(addSlashes($ex->getMessage()) . "\r");
    $factoids = array();
    $gamelist = array();
  }
  $collection = array();
  $collection['games'] = $gamelist;
  $collection['factoids'] = $factoids;
  return json_encode($collection);
}

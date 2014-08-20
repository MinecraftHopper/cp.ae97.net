CREATE DATABASE IF NOT EXISTS `authentication`
USE `authentication`;

CREATE TABLE IF NOT EXISTS `groupperms` (
  `groupId` int(11) unsigned NOT NULL,
  `permission` int(6) unsigned NOT NULL,
  KEY `FK_groupperms_groups` (`groupId`),
  KEY `FK_groupperms_permissions` (`permission`),
  CONSTRAINT `FK_groupperms_groups` FOREIGN KEY (`groupId`) REFERENCES `groups` (`groupId`),
  CONSTRAINT `FK_groupperms_permissions` FOREIGN KEY (`permission`) REFERENCES `permissions` (`permId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `groups` (
  `groupId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `rank` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`groupId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `passwordreset` (
  `userUUID` char(36) NOT NULL,
  `resetkey` varchar(64) NOT NULL,
  PRIMARY KEY (`userUUID`),
  CONSTRAINT `FK_passwordreset_users` FOREIGN KEY (`userUUID`) REFERENCES `users` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `permissions` (
  `permId` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `perm` varchar(64) NOT NULL,
  PRIMARY KEY (`permId`),
  UNIQUE KEY `perm` (`perm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `session` (
  `userId` int(11) unsigned zerofill NOT NULL,
  `sessionToken` varchar(256) DEFAULT NULL,
  PRIMARY KEY (`userId`),
  CONSTRAINT `FK_session_users` FOREIGN KEY (`userId`) REFERENCES `users` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `usergroups` (
  `userId` int(11) unsigned DEFAULT NULL,
  `groupId` int(11) unsigned DEFAULT NULL,
  KEY `FK_usergroups_users` (`userId`),
  KEY `FK_usergroups_groups` (`groupId`),
  CONSTRAINT `FK_usergroups_groups` FOREIGN KEY (`groupId`) REFERENCES `groups` (`groupId`),
  CONSTRAINT `FK_usergroups_users` FOREIGN KEY (`userId`) REFERENCES `users` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `users` (
  `userId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `username` varchar(64) NOT NULL,
  `email` varchar(64) NOT NULL,
  `password` varchar(64) NOT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT '0',
  `approved` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`userId`),
  UNIQUE KEY `unique_email` (`email`),
  UNIQUE KEY `unique_username` (`username`),
  UNIQUE KEY `unique_uuid` (`uuid`),
  KEY `verified` (`verified`),
  KEY `approved` (`approved`),
  KEY `username` (`username`),
  KEY `email` (`email`),
  KEY `uuid` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `verification` (
  `userId` int(11) unsigned NOT NULL,
  KEY `FK__users` (`userId`),
  CONSTRAINT `FK__users` FOREIGN KEY (`userId`) REFERENCES `users` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE DATABASE IF NOT EXISTS `factoid` 
USE `factoid`;

CREATE TABLE IF NOT EXISTS `factoids` (
  `id` int(32) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `game` smallint(6) unsigned NOT NULL DEFAULT '0',
  `content` mediumtext NOT NULL,
  PRIMARY KEY (`name`,`game`),
  UNIQUE KEY `unique_id` (`id`),
  KEY `FK_factoids_games` (`game`),
  KEY `key_id` (`id`),
  CONSTRAINT `FK_factoids_games` FOREIGN KEY (`game`) REFERENCES `games` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `games` (
  `id` smallint(6) unsigned NOT NULL AUTO_INCREMENT,
  `idname` varchar(255) NOT NULL,
  `displayname` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idname` (`idname`),
  UNIQUE KEY `displayname` (`displayname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

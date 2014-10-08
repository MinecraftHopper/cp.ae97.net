CREATE DATABASE IF NOT EXISTS `authentication` /*!40100 DEFAULT CHARACTER SET utf8 */;
CREATE DATABASE IF NOT EXISTS `factoid` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `authentication`;

CREATE TABLE IF NOT EXISTS `groupperms` (
  `groupId` int(11) unsigned NOT NULL,
  `permission` varchar(64) NOT NULL,
  KEY `FK_groupperms_groups` (`groupId`),
  KEY `FK_groupperms_permissions` (`permission`),
  CONSTRAINT `FK_groupperms_groups` FOREIGN KEY (`groupId`) REFERENCES `groups` (`groupId`),
  CONSTRAINT `FK_groupperms_permissions` FOREIGN KEY (`permission`) REFERENCES `permissions` (`perm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `groups` (
  `groupId` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `rank` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`groupId`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `passwordreset` (
  `uuid` varchar(36) NOT NULL,
  `resetkey` varchar(64) NOT NULL,
  PRIMARY KEY (`uuid`),
  CONSTRAINT `FK_passwordreset_users` FOREIGN KEY (`uuid`) REFERENCES `users` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `permissions` (
  `perm` varchar(64) NOT NULL,
  PRIMARY KEY (`perm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `session` (
  `uuid` varchar(36) NOT NULL,
  `sessionToken` varchar(256) NOT NULL,
  PRIMARY KEY (`uuid`),
  CONSTRAINT `FK_session_users` FOREIGN KEY (`uuid`) REFERENCES `users` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `usergroups` (
  `useruuid` varchar(36) NOT NULL,
  `groupId` int(11) unsigned NOT NULL DEFAULT '5',
  KEY `FK_usergroups_users` (`useruuid`),
  KEY `FK_usergroups_groups` (`groupId`),
  CONSTRAINT `FK_usergroups_groups` FOREIGN KEY (`groupId`) REFERENCES `groups` (`groupId`),
  CONSTRAINT `FK_usergroups_users` FOREIGN KEY (`useruuid`) REFERENCES `users` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `users` (
  `uuid` varchar(36) NOT NULL,
  `username` varchar(64) NOT NULL,
  `email` varchar(64) NOT NULL,
  `password` varchar(64) NOT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT '0',
  `approved` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`uuid`),
  UNIQUE KEY `unique_email` (`email`),
  UNIQUE KEY `unique_username` (`username`),
  KEY `username` (`username`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `verification` (
  `uuid` varchar(36) NOT NULL,
  `code` varchar(36) NOT NULL,
  KEY `FK_verification_users` (`uuid`),
  CONSTRAINT `FK_verification_users` FOREIGN KEY (`uuid`) REFERENCES `users` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

USE `factoid`;

CREATE TABLE IF NOT EXISTS `factoids` (
  `id` int(32) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `game` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `content` varchar(1024) NOT NULL,
  PRIMARY KEY (`name`,`game`),
  UNIQUE KEY `unique_id` (`id`),
  KEY `FK_factoids_games` (`game`),
  KEY `key_id` (`id`),
  CONSTRAINT `FK_factoids_games` FOREIGN KEY (`game`) REFERENCES `games` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `games` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `idname` varchar(255) NOT NULL,
  `displayname` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idname` (`idname`),
  UNIQUE KEY `displayname` (`displayname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE USER 'panel'@'localhost' IDENTIFIED BY '';
GRANT ALL ON authentication.* TO 'panel'@'localhost';
GRANT ALL ON factoid.* TO 'panel'@'localhost';
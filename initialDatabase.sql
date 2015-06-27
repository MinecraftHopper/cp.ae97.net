DROP DATABASE IF EXISTS `panel`;
CREATE DATABASE `panel` DEFAULT CHARSET=utf8;
USE `panel`;

CREATE TABLE `users` (
  `uuid` varchar(36) NOT NULL,
  `username` varchar(64) NOT NULL,
  `email` varchar(64) NOT NULL,
  `password` varchar(64) NOT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT '0',
  `approved` tinyint(1) NOT NULL DEFAULT '0',
  `nickserv` varchar(64),
  PRIMARY KEY (`uuid`),
  UNIQUE KEY `unique_email` (`email`),
  UNIQUE KEY `unique_username` (`username`),
  KEY `username` (`username`),
  KEY `email` (`email`)
) ENGINE=InnoDB;

CREATE TABLE `games` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
  `idname` varchar(255) NOT NULL,
  `displayname` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idname` (`idname`),
  UNIQUE KEY `displayname` (`displayname`)
) ENGINE=InnoDB;

CREATE TABLE `bans` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `content` varchar(256) NOT NULL,
  `issuedBy` varchar(36) NOT NULL,
  `kickMessage` varchar(512) NOT NULL DEFAULT 'You are banned from this channel',
  `notes` text,
  `issueDate` datetime NOT NULL,
  `expireDate` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `FK_bans_users` (`issuedBy`),
  CONSTRAINT `FK_bans_users` FOREIGN KEY (`issuedBy`) REFERENCES `users` (`uuid`)
) ENGINE=InnoDB;

CREATE TABLE `banchannels` (
  `banId` int(10) unsigned NOT NULL,
  `channel` varchar(64) NOT NULL,
  PRIMARY KEY (`banId`,`channel`),
  CONSTRAINT `FK_banchannels_bans` FOREIGN KEY (`banId`) REFERENCES `bans` (`id`)
) ENGINE=InnoDB;

CREATE TABLE `factoids` (
  `id` int(32) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `game` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `content` varchar(1024) NOT NULL,
  PRIMARY KEY (`name`,`game`),
  UNIQUE KEY `unique_id` (`id`),
  KEY `FK_factoids_games` (`game`),
  KEY `key_id` (`id`),
  CONSTRAINT `FK_factoids_games` FOREIGN KEY (`game`) REFERENCES `games` (`id`)
) ENGINE=InnoDB;

CREATE TABLE `passwordreset` (
  `uuid` varchar(36) NOT NULL,
  `resetkey` varchar(64) NOT NULL,
  PRIMARY KEY (`uuid`),
  CONSTRAINT `FK_passwordreset_users` FOREIGN KEY (`uuid`) REFERENCES `users` (`uuid`)
) ENGINE=InnoDB;

CREATE TABLE `permissions` (
  `id` int(2) NOT NULL AUTO_INCREMENT,
  `perm` varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `perm` (`perm`)
) ENGINE=InnoDB;

CREATE TABLE `userperms` (
  `userid` varchar(36) NOT NULL,
  `permission` int(2) NOT NULL,
  PRIMARY KEY (`userid`,`permission`),
  KEY `FK_userperms_permissions` (`permission`),
  CONSTRAINT `FK_userperms_permissions` FOREIGN KEY (`permission`) REFERENCES `permissions` (`id`),
  CONSTRAINT `FK_userperms_users` FOREIGN KEY (`userid`) REFERENCES `users` (`uuid`)
) ENGINE=InnoDB;

CREATE TABLE `session` (
  `uuid` varchar(36) NOT NULL,
  `sessionToken` varchar(256) NOT NULL,
  PRIMARY KEY (`uuid`),
  CONSTRAINT `FK_session_users` FOREIGN KEY (`uuid`) REFERENCES `users` (`uuid`)
) ENGINE=InnoDB;

CREATE TABLE `verification` (
  `email` varchar(64) NOT NULL,
  `code` varchar(36) NOT NULL,
  PRIMARY KEY (`email`),
  CONSTRAINT `FK_verification_users` FOREIGN KEY (`email`) REFERENCES `users` (`email`)
) ENGINE=InnoDB;

CREATE TABLE `factoid_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user` varchar(36) NOT NULL,
  `action` enum ('delete', 'create', 'edit', 'rename', 'move') NOT NULL,
  `factoidid` int(10) unsigned NOT NULL,
  `data` text,
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_factoid_logs_user` FOREIGN KEY (`user`) REFERENCES `users` (`uuid`)
) ENGINE=InnoDB;

CREATE USER 'panel'@'localhost' IDENTIFIED BY '';
GRANT ALL ON authentication.* TO 'panel'@'localhost';
GRANT ALL ON factoid.* TO 'panel'@'localhost';
